<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\BookingAccount;

/** Lectura del valor a cobrar de un expediente (servicios vigentes) y de las penalidades registradas. */
interface BookingAccounts
{
    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    public function account(string $bookingUlid): BookingAccount;

    /**
     * Expedientes vigentes cuyo primer servicio activo es en esa fecha (para recordatorios de saldo).
     *
     * @return list<string>
     */
    public function startingOn(\Carbon\CarbonImmutable $date): array;
}
