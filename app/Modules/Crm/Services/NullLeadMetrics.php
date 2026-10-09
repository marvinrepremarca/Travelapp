<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Contracts\LeadMetrics;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Comercial apagada: sin prospectos en los tableros. */
final class NullLeadMetrics implements LeadMetrics
{
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return 0;
    }

    public function openFor(int $ownerId, int $limit): array
    {
        return [];
    }
}
