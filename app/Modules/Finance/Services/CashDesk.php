<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Operaciones de la caja diaria: buscar la abierta, registrar movimientos y calcular el saldo esperado. */
final class CashDesk implements CashRegister
{
    public function openSessionFor(int $branchId, bool $lock = false): ?CashSession
    {
        return CashSession::query()
            ->where('open_branch_key', $branchId)
            ->when($lock, static fn($query) => $query->lockForUpdate())
            ->first();
    }

    public function recordPaymentIncome(User $receiver, Money $amount, string $description, string $paymentUlid): void
    {
        $session = $receiver->branch_id === null ? null : $this->openSessionFor($receiver->branch_id, lock: true);
        if (! $session instanceof CashSession) {
            throw FinanceRuleViolation::cashSessionClosed();
        }

        $this->record($session, $receiver, CashMovementType::Income, $amount, $description, $paymentUlid);
    }

    public function record(CashSession $session, User $actor, CashMovementType $type, Money $amount, string $description, ?string $paymentUlid = null): CashMovement
    {
        if ($session->status !== CashSessionStatus::Open) {
            throw FinanceRuleViolation::cashSessionClosed();
        }

        if ($amount->getCurrency()->getCurrencyCode() !== $session->currency) {
            throw FinanceRuleViolation::cashCurrencyMismatch($session->currency);
        }

        return CashMovement::query()->create([
            'cash_session_id' => $session->id,
            'type' => $type,
            'amount_minor' => $amount->getMinorAmount()->toInt(),
            'description' => $description,
            'payment_ulid' => $paymentUlid,
            'recorded_by' => $actor->id,
            'recorded_at' => CarbonImmutable::now(),
        ]);
    }

    /** Base + entradas − salidas. */
    public function expected(CashSession $session): Money
    {
        $totals = CashMovement::query()
            ->where('cash_session_id', $session->id)
            ->toBase()
            ->selectRaw('type, sum(amount_minor) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return $session->money($session->opening_amount_minor
            + (int) $totals->get(CashMovementType::Income->value, 0)
            - (int) $totals->get(CashMovementType::Expense->value, 0));
    }
}
