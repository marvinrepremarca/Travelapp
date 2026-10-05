<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Payments\Data\BalanceSummary;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Saldo del expediente en su moneda de venta. El saldo vence N días ⚙ antes del primer servicio vigente
 * (fecha de calendario en la zona de la agencia).
 */
final class PaymentLedger
{
    public function summary(BookingAccount $account, CarbonImmutable $now): BalanceSummary
    {
        $currency = $account->saleTotal->getCurrency()->getCurrencyCode();
        $totals = Payment::query()
            ->where('booking_ulid', $account->ulid)
            ->whereIn('status', [PaymentStatus::Approved, PaymentStatus::Pending])
            ->where('currency', $currency)
            ->toBase()
            ->selectRaw('status, sum(amount_minor) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $paid = Money::ofMinor((int) $totals->get(PaymentStatus::Approved->value, 0), $currency);
        $pending = Money::ofMinor((int) $totals->get(PaymentStatus::Pending->value, 0), $currency);
        $balance = $account->saleTotal->minus($paid);

        $dueDate = $account->firstServiceDate?->subDays(config()->integer('travel.payments.balance_due_days_before'));
        $today = CarbonImmutable::parse($now->setTimezone(config()->string('travel.agency.timezone'))->toDateString());

        return new BalanceSummary(
            total: $account->saleTotal,
            paid: $paid,
            pending: $pending,
            balance: $balance,
            dueDate: $dueDate,
            isOverdue: $dueDate instanceof CarbonImmutable && $balance->isPositive() && $today->greaterThan($dueDate),
        );
    }
}
