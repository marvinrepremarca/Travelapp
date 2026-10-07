<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Bookings\Enums\BookingStatus;

/** Expediente para el portal del viajero. */
final readonly class Trip
{
    /** @param  list<TripService>  $services  en orden de fecha */
    public function __construct(
        public string $ulid,
        public string $number,
        public string $title,
        public BookingStatus $status,
        public int $customerId,
        public string $customerName,
        public int $ownerId,
        public ?int $branchId,
        public array $services,
    ) {}
}
