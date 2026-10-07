<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\ScheduledDeparture;
use Carbon\CarbonImmutable;

/** Salidas del producto propio para la operación (Operations). */
interface DepartureSchedule
{
    /** @return list<ScheduledDeparture> salidas entre dos fechas (inclusive), por fecha y hora */
    public function between(CarbonImmutable $from, CarbonImmutable $to): array;

    public function find(string $departureUlid): ?ScheduledDeparture;

    /**
     * @param  list<string>  $departureUlids
     * @return list<ScheduledDeparture>
     */
    public function findMany(array $departureUlids): array;
}
