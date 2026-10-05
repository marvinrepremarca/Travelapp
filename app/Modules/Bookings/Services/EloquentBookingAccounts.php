<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use Brick\Money\Money;

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
}
