<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Payments\Data\BalanceSummary;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Refund;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Estado de cuenta del expediente en su moneda de venta. El saldo vence N días ⚙ antes del primer servicio vigente
 * (fecha de calendario en la zona de la agencia).
 */
final class PaymentLedger
{
    public function summary(BookingAccount $account, CarbonImmutable $now): BalanceSummary
    {
        $currency = $account->saleTotal->getCurrency()->getCurrencyCode();
        $payments = $this->totals(Payment::class, $account->ulid, $currency, [PaymentStatus::Approved->value, PaymentStatus::Pending->value]);
        $refunds = $this->totals(Refund::class, $account->ulid, $currency, [RefundStatus::Requested->value, RefundStatus::Approved->value, RefundStatus::Paid->value]);

        $money = static fn(array $totals, string $status): Money => Money::ofMinor($totals[$status] ?? 0, $currency);
        $total = $account->saleTotal->plus($account->penaltiesTotal);
        $paid = $money($payments, PaymentStatus::Approved->value);
        $refunded = $money($refunds, RefundStatus::Paid->value);
        $balance = $total->minus($paid)->plus($refunded);

        $dueDate = $account->firstServiceDate?->subDays(config()->integer('travel.payments.balance_due_days_before'));
        $today = CarbonImmutable::parse($now->setTimezone(config()->string('travel.agency.timezone'))->toDateString());

        return new BalanceSummary(
            total: $total,
            paid: $paid,
            pending: $money($payments, PaymentStatus::Pending->value),
            refunded: $refunded,
            refundsInProgress: $money($refunds, RefundStatus::Requested->value)->plus($money($refunds, RefundStatus::Approved->value)),
            balance: $balance,
            dueDate: $dueDate,
            isOverdue: $dueDate instanceof CarbonImmutable && $balance->isPositive() && $today->greaterThan($dueDate),
        );
    }

    /**
     * Suma por estado en una sola consulta agrupada.
     *
     * @param  class-string<Payment|Refund>  $model
     * @param  list<string>  $statuses
     * @return array<string, int>
     */
    private function totals(string $model, string $bookingUlid, string $currency, array $statuses): array
    {
        return $model::query()
            ->where('booking_ulid', $bookingUlid)
            ->where('currency', $currency)
            ->whereIn('status', $statuses)
            ->toBase()
            ->selectRaw('status, sum(amount_minor) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(static fn(mixed $total): int => (int) $total)
            ->all();
    }
}
