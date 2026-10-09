<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use Carbon\CarbonImmutable;

/** Expediente con un servicio próximo (fecha local del destino). */
final readonly class UpcomingTrip
{
    public function __construct(
        public string $ulid,
        public string $number,
        public string $customerName,
        public CarbonImmutable $nextServiceDate,
    ) {}
}
