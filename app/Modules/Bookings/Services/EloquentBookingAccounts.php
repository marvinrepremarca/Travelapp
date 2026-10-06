<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

final class EloquentBookingAccounts implements BookingAccounts
{
    public function account(string $bookingUlid): BookingAccount
    {
        $booking = Booking::query()->with(['customer:id,display_name', 'items'])->where('ulid', $bookingUlid)->firstOrFail();
        $active = $booking->items->reject(static fn(BookingItem $item): bool => $item->status->isClosed());
        $penalties = $booking->items->reduce(
            static fn(Money $carry, BookingItem $item): Money => $item->penaltyAmount() instanceof Money ? $carry->plus($item->penaltyAmount()) : $carry,
            Money::zero($booking->sale_currency),
        );

        return new BookingAccount(
            ulid: $booking->ulid,
            number: (string) $booking->number,
            title: $booking->title,
            customerId: $booking->customer_id,
            customerName: $booking->customer->display_name,
            ownerId: $booking->owner_id,
            branchId: $booking->branch_id,
            saleTotal: $booking->saleTotal(),
            penaltiesTotal: $penalties,
            firstServiceDate: $active->min('service_date'),
        );
    }

    public function startingOn(CarbonImmutable $date): array
    {
        $closed = array_values(array_map(static fn(BookingItemStatus $status): string => $status->value, array_filter(BookingItemStatus::cases(), static fn(BookingItemStatus $status): bool => $status->isClosed())));

        return array_values(Booking::query()
            ->whereIn('bookings.status', [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::NeedsAttention])
            ->join('booking_items', 'booking_items.booking_id', '=', 'bookings.id')
            ->whereNotIn('booking_items.status', $closed)
            ->groupBy('bookings.ulid')
            ->havingRaw('min(booking_items.service_date) = ?', [$date->toDateString()])
            ->pluck('bookings.ulid')
            ->map(static fn(mixed $ulid): string => (string) $ulid)
            ->all());
    }
}
