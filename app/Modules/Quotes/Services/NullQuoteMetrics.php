<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Services;

use App\Modules\Quotes\Contracts\QuoteMetrics;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Cotizaciones apagada: sin cotizaciones en los tableros. */
final class NullQuoteMetrics implements QuoteMetrics
{
    public function firstSentBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return 0;
    }

    public function acceptedBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return 0;
    }

    public function expiringFor(int $ownerId, CarbonImmutable $now, CarbonImmutable $until, int $limit): array
    {
        return [];
    }
}
