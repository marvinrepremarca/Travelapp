<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Contracts;

use App\Modules\Quotes\Data\ExpiringQuote;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Indicadores de la capacidad Cotizaciones para los tableros (ADR-0007). Con Cotizaciones apagada todo vale cero. */
interface QuoteMetrics
{
    /** Cotizaciones enviadas por primera vez en [from, until). */
    public function firstSentBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int;

    /** Cotizaciones aceptadas en [from, until). */
    public function acceptedBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int;

    /**
     * Cotizaciones enviadas del asesor que vencen entre now y until, las más próximas primero.
     *
     * @return list<ExpiringQuote>
     */
    public function expiringFor(int $ownerId, CarbonImmutable $now, CarbonImmutable $until, int $limit): array;
}
