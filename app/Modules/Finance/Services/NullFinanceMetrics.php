<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\FinanceMetrics;
use App\Modules\Shared\ValueObjects\AgingBuckets;
use Carbon\CarbonImmutable;

/** Contabilidad apagada: sin cuentas por pagar ni cajas en los tableros. */
final class NullFinanceMetrics implements FinanceMetrics
{
    public function payablesAging(CarbonImmutable $today, CarbonImmutable $dueSoonUntil, string $currency): AgingBuckets
    {
        return AgingBuckets::zero($currency);
    }

    public function openCash(): array
    {
        return [];
    }
}
