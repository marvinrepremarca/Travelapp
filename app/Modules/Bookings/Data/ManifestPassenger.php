<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

/** Pasajero del manifiesto de una salida (documento enmascarado). */
final readonly class ManifestPassenger
{
    public function __construct(
        public string $bookingNumber,
        public string $bookingUlid,
        public string $travelerName,
        public int $age,
        public string $passengerType,
        public string $nationality,
        public string $maskedDocument,
        public ?string $contactPhone,
    ) {}
}
