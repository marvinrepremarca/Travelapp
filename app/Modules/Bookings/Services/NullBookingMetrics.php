<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Reservas apagada: sin expedientes en los tableros. */
final class NullBookingMetrics implements BookingMetrics
{
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return 0;
    }

    public function upcomingFor(int $ownerId, CarbonImmutable $fromDate, CarbonImmutable $untilDate, int $limit): array
    {
        return [];
    }

    public function receivableBases(ScopedViewer $viewer, string $currency, int $limit): array
    {
        return [];
    }
}
