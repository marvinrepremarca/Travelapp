<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\ManifestPassenger;

/** Pasajeros confirmados en cada salida de producto propio (para manifiestos de operación). */
interface DepartureManifests
{
    /** @return list<ManifestPassenger> */
    public function passengersOf(string $departureUlid): array;

    /**
     * @param  list<string>  $departureUlids
     * @return array<string, int> salida → pasajeros confirmados
     */
    public function countsFor(array $departureUlids): array;
}
