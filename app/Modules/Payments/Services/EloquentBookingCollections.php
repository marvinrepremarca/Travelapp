<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Refund;
use Brick\Money\Money;
use Illuminate\Support\Collection;

/** Dos consultas agrupadas para cualquier cantidad de expedientes. */
final class EloquentBookingCollections implements BookingCollections
{
    public function netCollected(array $currencyByBooking): array
    {
        if ($currencyByBooking === []) {
            return [];
        }

        $paid = $this->sums(Payment::query()->where('status', PaymentStatus::Approved)->whereIn('booking_ulid', array_keys($currencyByBooking))->toBase()->selectRaw('booking_ulid, currency, sum(amount_minor) as total')->groupBy('booking_ulid', 'currency')->get());
        $refunded = $this->sums(Refund::query()->where('status', RefundStatus::Paid)->whereIn('booking_ulid', array_keys($currencyByBooking))->toBase()->selectRaw('booking_ulid, currency, sum(amount_minor) as total')->groupBy('booking_ulid', 'currency')->get());

        $collected = [];
        foreach ($currencyByBooking as $ulid => $currency) {
            $collected[$ulid] = Money::ofMinor(($paid[$ulid][$currency] ?? 0) - ($refunded[$ulid][$currency] ?? 0), $currency);
        }

        return $collected;
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @return array<string, array<string, int>>
     */
    private function sums(Collection $rows): array
    {
        $sums = [];
        foreach ($rows as $row) {
            $sums[(string) $row->booking_ulid][(string) $row->currency] = (int) $row->total;
        }

        return $sums;
    }
}
