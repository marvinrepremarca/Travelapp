<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Reports\Data\AgingBuckets;
use App\Modules\Reports\Data\Period;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * KPI Cuentas por pagar (por vencimiento), caja abierta por sucursal y facturación del período, en la moneda de la agencia.
 */
final class FinanceSnapshotQuery
{
    public function payables(CarbonImmutable $today): AgingBuckets
    {
        $currency = config()->string('travel.agency.default_currency');
        $soon = $today->addDays(config()->integer('travel.reports.due_soon_days'))->toDateString();
        $row = SupplierPayable::query()
            ->where('status', PayableStatus::Open)
            ->where('currency', $currency)
            ->toBase()
            ->selectRaw('sum(case when due_date < ? then amount_minor else 0 end) as overdue', [$today->toDateString()])
            ->selectRaw('sum(case when due_date >= ? and due_date <= ? then amount_minor else 0 end) as soon', [$today->toDateString(), $soon])
            ->selectRaw('sum(case when due_date > ? then amount_minor else 0 end) as later', [$soon])
            ->selectRaw('count(*) as items')
            ->first();

        return new AgingBuckets(
            Money::ofMinor((int) ($row->overdue ?? 0), $currency),
            Money::ofMinor((int) ($row->soon ?? 0), $currency),
            Money::ofMinor((int) ($row->later ?? 0), $currency),
            (int) ($row->items ?? 0),
        );
    }

    /**
     * Efectivo esperado en cada caja abierta: base + entradas − salidas.
     *
     * @return list<array{branch: string, expected: Money, opened_at: CarbonImmutable}>
     */
    public function openCash(): array
    {
        $sessions = CashSession::query()->where('status', CashSessionStatus::Open)->with('branch:id,name')->get();
        if ($sessions->isEmpty()) {
            return [];
        }

        $totals = CashMovement::query()
            ->whereIn('cash_session_id', $sessions->pluck('id'))
            ->toBase()
            ->selectRaw('cash_session_id, type, sum(amount_minor) as total')
            ->groupBy('cash_session_id', 'type')
            ->get()
            ->groupBy('cash_session_id');

        return array_values($sessions->map(static function (CashSession $session) use ($totals): array {
            $byType = ($totals->get($session->id) ?? collect())->pluck('total', 'type');
            $expected = $session->opening_amount_minor
                + (int) $byType->get(CashMovementType::Income->value, 0)
                - (int) $byType->get(CashMovementType::Expense->value, 0)
                - (int) $byType->get(CashMovementType::BankDeposit->value, 0);

            return ['branch' => $session->branch->name ?? '—', 'expected' => $session->money($expected), 'opened_at' => $session->opened_at];
        })->all());
    }

    /** @return array<string, Money> tipo de documento → total emitido en el período */
    public function invoicing(Period $period): array
    {
        $currency = config()->string('travel.agency.default_currency');
        $totals = Invoice::query()
            ->where('currency', $currency)
            ->where('issued_at', '>=', $period->from())
            ->where('issued_at', '<', $period->until())
            ->toBase()
            ->selectRaw('type, sum(total_minor) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $result = [];
        foreach (InvoiceType::cases() as $type) {
            $result[$type->value] = Money::ofMinor((int) $totals->get($type->value, 0), $currency);
        }

        return $result;
    }
}
