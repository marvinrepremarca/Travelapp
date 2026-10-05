<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\PaymentLedger;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Registra un abono por transferencia (queda pendiente de validación de finanzas) o en efectivo (aprobado).
 * Nunca se cobra más que el saldo, contando lo que está pendiente de confirmar.
 */
final readonly class RecordPaymentAction
{
    public function __construct(
        private PaymentLedger $ledger,
        private MoneyPresenter $presenter,
        private CashRegister $cash,
    ) {}

    public function execute(User $actor, BookingAccount $account, PaymentMethod $method, Money $amount, ?string $reference, ?string $note, CarbonImmutable $now): Payment
    {
        return DB::transaction(function () use ($actor, $account, $method, $amount, $reference, $note, $now): Payment {
            // Serializa los abonos del mismo expediente para que dos registros simultáneos no superen el saldo.
            Payment::query()->where('booking_ulid', $account->ulid)->lockForUpdate()->get(['id']);
            $this->assertWithinBalance($account, $amount, $now);

            $payment = new Payment([
                'booking_ulid' => $account->ulid,
                'method' => $method,
                'amount_minor' => $amount->getMinorAmount()->toInt(),
                'currency' => $amount->getCurrency()->getCurrencyCode(),
                'reference' => $reference,
                'note' => $note,
            ]);
            $payment->owner_id = $account->ownerId;
            $payment->branch_id = $account->branchId;
            $payment->status = $method->initialStatus();
            $payment->recorded_by = $actor->id;
            $payment->idempotency_key = (string) Str::ulid();
            $payment->approved_at = $method->initialStatus() === PaymentStatus::Approved ? $now : null;
            $payment->save();

            // El efectivo entra a la caja abierta de la sucursal de quien lo recibe; sin caja abierta no se recibe.
            if ($method === PaymentMethod::Cash) {
                $this->cash->recordPaymentIncome($actor, $amount, __('payments.cash_income', ['number' => $account->number]), $payment->ulid);
            }

            return $payment;
        });
    }

    public function assertWithinBalance(BookingAccount $account, Money $amount, CarbonImmutable $now): void
    {
        $collectable = $this->ledger->summary($account, $now)->collectable();
        if ($amount->isGreaterThan($collectable)) {
            throw PaymentRuleViolation::exceedsBalance($this->presenter->format($collectable));
        }
    }
}
