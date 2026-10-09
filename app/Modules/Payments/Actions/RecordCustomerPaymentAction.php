<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Payment;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Abono de un cliente sin expediente (ADR-0007): anticipo o venta externa. No hay saldo contra el cual validar;
 * el efectivo entra a la caja (si Contabilidad está encendida) y la transferencia queda por validar.
 */
final readonly class RecordCustomerPaymentAction
{
    public function __construct(private CashRegister $cash) {}

    public function execute(User $actor, Customer $customer, PaymentMethod $method, Money $amount, string $concept, ?string $reference, CarbonImmutable $now): Payment
    {
        if (! $amount->isPositive()) {
            throw PaymentRuleViolation::invalidAmount();
        }

        if ($method === PaymentMethod::OnlineLink) {
            throw PaymentRuleViolation::methodNotAllowedWithoutBooking();
        }

        return DB::transaction(function () use ($actor, $customer, $method, $amount, $concept, $reference, $now): Payment {
            $payment = new Payment([
                'method' => $method,
                'amount_minor' => $amount->getMinorAmount()->toInt(),
                'currency' => $amount->getCurrency()->getCurrencyCode(),
                'reference' => $reference,
            ]);
            $payment->customer_id = $customer->id;
            $payment->concept = $concept;
            $payment->owner_id = $customer->owner_id;
            $payment->branch_id = $customer->branch_id;
            $payment->status = $method->initialStatus();
            $payment->recorded_by = $actor->id;
            $payment->idempotency_key = (string) Str::ulid();
            $payment->approved_at = $method->initialStatus() === PaymentStatus::Approved ? $now : null;
            $payment->save();

            if ($payment->status === PaymentStatus::Approved) {
                PaymentReceived::of($payment)->publish();
            }

            if ($method === PaymentMethod::Cash) {
                $this->cash->recordPaymentIncome($actor, $amount, __('payments.customer_payments.cash_income', ['customer' => $customer->display_name]), $payment->ulid);
            }

            return $payment;
        });
    }
}
