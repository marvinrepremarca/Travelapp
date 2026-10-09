<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\ReceivableBase;
use App\Modules\Bookings\Data\UpcomingTrip;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Indicadores de la capacidad Reservas para los tableros (ADR-0007). Con Reservas apagada todo vale cero. */
interface BookingMetrics
{
    /** Expedientes creados en [from, until). */
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int;

    /**
     * Expedientes del asesor con servicios vigentes entre dos fechas locales del destino, el más próximo primero.
     *
     * @return list<UpcomingTrip>
     */
    public function upcomingFor(int $ownerId, CarbonImmutable $fromDate, CarbonImmutable $untilDate, int $limit): array;

    /**
     * Expedientes vigentes en la moneda dada con lo que el cliente debe pagar (venta + penalidades), para la cartera.
     *
     * @return list<ReceivableBase>
     */
    public function receivableBases(ScopedViewer $viewer, string $currency, int $limit): array;
}
