<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use App\Modules\Catalog\Enums\DepartureStatus;
use Carbon\CarbonImmutable;

/** Salida programada de un producto propio, con su horario local en el destino. */
final readonly class ScheduledDeparture
{
    public function __construct(
        public string $ulid,
        public string $productUlid,
        public string $productName,
        public string $destinationCity,
        public string $timezone,
        public CarbonImmutable $serviceDate,
        public string $startsAt,
        public ?int $durationMinutes,
        public int $capacity,
        public int $reservedSeats,
        public DepartureStatus $status,
    ) {}

    /** Inicio local en el destino. */
    public function startsAtLocal(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->serviceDate->toDateString() . ' ' . $this->startsAt, $this->timezone);
    }

    /** Fin local: inicio + duración ⚙ (sin duración se asume el día completo). */
    public function endsAtLocal(): CarbonImmutable
    {
        return $this->durationMinutes === null
            ? $this->startsAtLocal()->endOfDay()
            : $this->startsAtLocal()->addMinutes($this->durationMinutes);
    }
}
