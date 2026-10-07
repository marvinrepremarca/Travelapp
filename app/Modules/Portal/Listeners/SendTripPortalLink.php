<?php

declare(strict_types=1);

namespace App\Modules\Portal\Listeners;

use App\Modules\Bookings\Contracts\TravelerTrips;
use App\Modules\Bookings\Data\Trip;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Portal\Services\TripAccessNotifier;

/** Cuando el último servicio se confirma y el expediente queda confirmado, el titular recibe su acceso a "Mi viaje". */
final readonly class SendTripPortalLink
{
    public function __construct(
        private TravelerTrips $trips,
        private TripAccessNotifier $notifier,
    ) {}

    public function handle(BookingItemConfirmed $event): void
    {
        $trip = $this->trips->trip($event->bookingUlid);
        if ($trip instanceof Trip && $trip->status === BookingStatus::Confirmed) {
            $this->notifier->send($trip, 'trip_portal:' . $trip->ulid);
        }
    }
}
