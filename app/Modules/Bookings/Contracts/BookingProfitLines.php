<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\ProfitLine;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Venta vigente, costo y penalidades de los expedientes creados en un período, según el alcance de quien consulta. */
interface BookingProfitLines
{
    /**
     * @param  CarbonImmutable  $from  instante UTC inclusivo
     * @param  CarbonImmutable  $until  instante UTC exclusivo
     * @return list<ProfitLine>
     */
    public function soldBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $branchId, ?int $ownerId): array;
}
