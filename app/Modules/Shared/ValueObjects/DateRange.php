<?php

declare(strict_types=1);

namespace App\Modules\Shared\ValueObjects;

use App\Modules\Shared\Exceptions\InvalidDateRange;
use Carbon\CarbonImmutable;

/**
 * Rango de fechas de servicio (sin hora), local al destino.
 * Las noches se cuentan por diferencia de fechas, nunca de horas, para no fallar con cambios de horario.
 */
final readonly class DateRange
{
    public CarbonImmutable $start;

    public CarbonImmutable $end;

    public function __construct(CarbonImmutable $start, CarbonImmutable $end)
    {
        $start = $start->startOfDay();
        $end = $end->startOfDay();

        if ($end->lessThan($start)) {
            throw InvalidDateRange::endBeforeStart($start, $end);
        }

        $this->start = $start;
        $this->end = $end;
    }

    public static function fromStrings(string $start, string $end): self
    {
        return new self(CarbonImmutable::parse($start), CarbonImmutable::parse($end));
    }

    public function nights(): int
    {
        return (int) $this->start->diffInDays($this->end, absolute: true);
    }

    /** Días de servicio, incluidos el primero y el último (tours, alquileres por día). */
    public function days(): int
    {
        return $this->nights() + 1;
    }

    public function contains(CarbonImmutable $date): bool
    {
        $day = $date->startOfDay();

        return $day->greaterThanOrEqualTo($this->start) && $day->lessThanOrEqualTo($this->end);
    }

    /** Se solapan si comparten al menos una noche: la salida de uno puede ser la llegada del otro. */
    public function overlaps(self $other): bool
    {
        return $this->start->lessThan($other->end) && $other->start->lessThan($this->end);
    }

    /** @return list<CarbonImmutable> Fecha de cada noche, para tarifas por noche que cruzan temporadas. */
    public function eachNight(): array
    {
        $nights = [];

        for ($night = $this->start; $night->lessThan($this->end); $night = $night->addDay()) {
            $nights[] = $night;
        }

        return $nights;
    }

    public function equals(self $other): bool
    {
        return $this->start->equalTo($other->start) && $this->end->equalTo($other->end);
    }
}
