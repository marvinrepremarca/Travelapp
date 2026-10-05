<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

use Carbon\CarbonImmutable;

/** Búsqueda de hoteles por ciudad y país del destino, fechas locales y edades de los huéspedes. */
final readonly class HotelSearchCriteria
{
    /**
     * @param  list<int>  $guestAges
     */
    public function __construct(
        public string $city,
        public string $countryCode,
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public array $guestAges,
    ) {}

    /** Noches = diferencia de fechas, no de horas. */
    public function nights(): int
    {
        return (int) CarbonImmutable::parse($this->checkIn->toDateString())->diffInDays(CarbonImmutable::parse($this->checkOut->toDateString()));
    }

    public function hash(): string
    {
        return hash('sha256', implode('|', [
            mb_strtolower($this->city), mb_strtoupper($this->countryCode), $this->checkIn->toDateString(), $this->checkOut->toDateString(), implode(',', $this->guestAges),
        ]));
    }
}
