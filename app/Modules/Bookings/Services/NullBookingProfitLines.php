<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Reservas apagada: no hay ventas de expedientes que analizar. */
final class NullBookingProfitLines implements BookingProfitLines
{
    public function soldBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $branchId, ?int $ownerId): array
    {
        return [];
    }
}
