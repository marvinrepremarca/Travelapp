<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use Carbon\CarbonImmutable;

/** Cambia el estado de un servicio validando la máquina de estados y recalcula el estado del expediente. */
final class BookingItemWorkflow
{
    public function transition(BookingItem $item, BookingItemStatus $next, ?string $note, CarbonImmutable $now): void
    {
        if (! $item->status->canTransitionTo($next)) {
            throw BookingRuleViolation::invalidTransition($item->status, $next);
        }

        $item->status = $next;
        $item->status_note = $note;
        $item->status_changed_at = $now;
        $item->save();

        $this->syncBooking($item->booking()->firstOrFail());
    }

    public function syncBooking(Booking $booking): void
    {
        $statuses = array_values($booking->items()->get(['id', 'status'])->map(static fn(BookingItem $item): BookingItemStatus => $item->status)->all());
        $booking->status = BookingStatus::derive($statuses);
        $booking->save();
    }
}
