<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Data\ReconciliationEntry;
use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Enums\ReconciliationTarget;
use App\Modules\Finance\Models\BankStatementLine;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Payments\Contracts\ReceivedPayments;
use App\Modules\Payments\Data\ReceivedPayment;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Movimientos del sistema que deben aparecer en el banco: abonos (transferencia y en línea), liquidaciones
 * a proveedores y consignaciones de caja. Sugiere cruces por valor exacto, fecha cercana ⚙ y referencia.
 */
final readonly class ReconciliationCandidates
{
    public function __construct(private ReceivedPayments $payments) {}

    /**
     * Movimientos sin conciliar entre dos fechas (inclusive), ampliadas con la ventana de días.
     *
     * @return list<ReconciliationEntry>
     */
    public function unmatched(string $currency, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $start = $from->subDays($this->windowDays())->startOfDay();
        $end = $to->addDays($this->windowDays() + 1)->startOfDay();

        $entries = [
            ...$this->customerPayments($currency, $start, $end),
            ...$this->supplierSettlements($currency, $start, $end),
            ...$this->cashDeposits($currency, $start, $end),
        ];

        $matched = BankStatementLine::query()
            ->whereNotNull('matched_ulid')
            ->whereIn('matched_ulid', array_map(static fn(ReconciliationEntry $entry): string => $entry->ulid, $entries))
            ->pluck('matched_ulid')
            ->flip();

        return array_values(array_filter($entries, static fn(ReconciliationEntry $entry): bool => ! $matched->has($entry->ulid)));
    }

    /**
     * Sugerencias para una línea del banco: mismo valor con signo, dentro de la ventana; primero las que comparten referencia.
     *
     * @param  list<ReconciliationEntry>  $entries
     * @return list<ReconciliationEntry>
     */
    public function suggestionsFor(BankStatementLine $line, array $entries): array
    {
        $window = $this->windowDays();
        $suggestions = array_values(array_filter(
            $entries,
            static fn(ReconciliationEntry $entry): bool => $entry->amount->isEqualTo($line->amount())
                && abs((int) $entry->occurredOn->startOfDay()->diffInDays($line->posted_on->startOfDay(), false)) <= $window,
        ));

        usort($suggestions, fn(ReconciliationEntry $left, ReconciliationEntry $right): int => [$this->referenceMiss($line, $left), $this->distance($line, $left)]
            <=> [$this->referenceMiss($line, $right), $this->distance($line, $right)]);

        return $suggestions;
    }

    /** Un movimiento puntual del sistema (para validar un cruce elegido a mano). */
    public function find(ReconciliationTarget $target, string $ulid): ?ReconciliationEntry
    {
        $entries = match ($target) {
            ReconciliationTarget::CustomerPayment => array_map($this->fromPayment(...), $this->payments->find([$ulid])),
            ReconciliationTarget::SupplierSettlement => $this->settlements(SupplierPayable::query()->where('settlement_ulid', $ulid)),
            ReconciliationTarget::CashDeposit => $this->deposits(CashMovement::query()->where('ulid', $ulid)),
        };

        return $entries[0] ?? null;
    }

    /** @return list<ReconciliationEntry> */
    private function customerPayments(string $currency, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return array_map($this->fromPayment(...), $this->payments->approvedBetween($currency, $start, $end));
    }

    private function fromPayment(ReceivedPayment $payment): ReconciliationEntry
    {
        return new ReconciliationEntry(
            ReconciliationTarget::CustomerPayment,
            $payment->ulid,
            $payment->approvedAt,
            $payment->amount,
            $payment->reference,
            ReconciliationTarget::CustomerPayment->label() . ' · ' . $payment->method->label() . ($payment->reference === null ? '' : ' · ' . $payment->reference),
        );
    }

    /** @return list<ReconciliationEntry> */
    private function supplierSettlements(string $currency, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->settlements(SupplierPayable::query()
            ->where('currency', $currency)
            ->where('paid_at', '>=', $start)
            ->where('paid_at', '<', $end));
    }

    /**
     * Una liquidación = una salida del banco por la suma de sus obligaciones.
     *
     * @param  Builder<SupplierPayable>  $query
     * @return list<ReconciliationEntry>
     */
    private function settlements(Builder $query): array
    {
        $rows = $query->where('status', PayableStatus::Paid)
            ->whereNotNull('settlement_ulid')
            ->toBase()
            ->selectRaw('settlement_ulid, supplier_id, currency, max(payment_reference) as reference, min(paid_at) as paid_at, sum(amount_minor) as total')
            ->groupBy('settlement_ulid', 'supplier_id', 'currency')
            ->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $suppliers = Supplier::query()->withTrashed()->whereIn('id', $rows->pluck('supplier_id')->unique()->all())->pluck('trade_name', 'id');

        return array_values($rows->map(static fn(object $row): ReconciliationEntry => new ReconciliationEntry(
            ReconciliationTarget::SupplierSettlement,
            (string) $row->settlement_ulid,
            CarbonImmutable::parse((string) $row->paid_at),
            Money::ofMinor(-(int) $row->total, (string) $row->currency),
            $row->reference === null ? null : (string) $row->reference,
            ReconciliationTarget::SupplierSettlement->label() . ' · ' . ($suppliers[(int) $row->supplier_id] ?? '—') . ($row->reference === null ? '' : ' · ' . $row->reference),
        ))->all());
    }

    /** @return list<ReconciliationEntry> */
    private function cashDeposits(string $currency, CarbonImmutable $start, CarbonImmutable $end): array
    {
        return $this->deposits(CashMovement::query()
            ->whereHas('session', static fn(Builder $session) => $session->where('currency', $currency))
            ->where('recorded_at', '>=', $start)
            ->where('recorded_at', '<', $end));
    }

    /**
     * @param  Builder<CashMovement>  $query
     * @return list<ReconciliationEntry>
     */
    private function deposits(Builder $query): array
    {
        return array_values($query->where('type', CashMovementType::BankDeposit)
            ->with('session:id,currency,branch_id', 'session.branch:id,name')
            ->get()
            ->map(static fn(CashMovement $movement): ReconciliationEntry => new ReconciliationEntry(
                ReconciliationTarget::CashDeposit,
                $movement->ulid,
                $movement->recorded_at,
                $movement->session->money($movement->amount_minor),
                $movement->description,
                ReconciliationTarget::CashDeposit->label() . ' · ' . ($movement->session->branch->name ?? '—') . ' · ' . $movement->description,
            ))->all());
    }

    /** 0 si la referencia del banco contiene la del sistema (o viceversa), 1 si no. */
    private function referenceMiss(BankStatementLine $line, ReconciliationEntry $entry): int
    {
        $bank = Str::lower($line->reference . ' ' . $line->description);
        $system = Str::lower(trim((string) $entry->reference));

        return $system !== '' && str_contains($bank, $system) ? 0 : 1;
    }

    private function distance(BankStatementLine $line, ReconciliationEntry $entry): int
    {
        return abs((int) $entry->occurredOn->startOfDay()->diffInDays($line->posted_on->startOfDay(), false));
    }

    private function windowDays(): int
    {
        return config()->integer('travel.finance.reconciliation_window_days');
    }
}
