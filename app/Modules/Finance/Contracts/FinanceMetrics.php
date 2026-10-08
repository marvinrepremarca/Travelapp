<?php

declare(strict_types=1);

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\Data\OpenCashBalance;
use App\Modules\Shared\ValueObjects\AgingBuckets;
use Carbon\CarbonImmutable;

/** Indicadores de la capacidad Contabilidad para los tableros (ADR-0007). Con Contabilidad apagada todo vale cero. */
interface FinanceMetrics
{
    /** Cuentas por pagar abiertas en la moneda dada, agrupadas por vencimiento respecto de hoy. */
    public function payablesAging(CarbonImmutable $today, CarbonImmutable $dueSoonUntil, string $currency): AgingBuckets;

    /**
     * Efectivo esperado en cada caja abierta: base + entradas − salidas.
     *
     * @return list<OpenCashBalance>
     */
    public function openCash(): array;
}
