<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

/** Tramo de vuelo. Las horas son locales del aeropuerto (ISO 8601 sin conversión), como las entrega la aerolínea. */
final readonly class FlightSegment
{
    public function __construct(
        public string $origin,
        public string $destination,
        public string $departsAtLocal,
        public string $arrivesAtLocal,
        public string $carrierCode,
        public string $carrierName,
        public string $flightNumber,
    ) {}
}
