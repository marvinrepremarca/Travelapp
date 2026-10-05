<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

use App\Modules\Search\Enums\CabinClass;
use Carbon\CarbonImmutable;

/** Búsqueda de vuelos: aeropuertos IATA, fechas locales y edad de cada pasajero a la fecha de salida. */
final readonly class FlightSearchCriteria
{
    /**
     * @param  list<int>  $passengerAges
     */
    public function __construct(
        public string $origin,
        public string $destination,
        public CarbonImmutable $departureDate,
        public array $passengerAges,
        public ?CarbonImmutable $returnDate = null,
        public CabinClass $cabin = CabinClass::Economy,
    ) {}

    /** Llave estable para caché e idempotencia de la búsqueda. */
    public function hash(): string
    {
        return hash('sha256', implode('|', [
            mb_strtoupper($this->origin), mb_strtoupper($this->destination), $this->departureDate->toDateString(),
            (string) $this->returnDate?->toDateString(), implode(',', $this->passengerAges), $this->cabin->value,
        ]));
    }
}
