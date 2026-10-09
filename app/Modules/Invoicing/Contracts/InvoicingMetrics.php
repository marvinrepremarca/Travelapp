<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Contracts;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Indicadores de la capacidad Facturación para los tableros (ADR-0007). Con Facturación apagada todo vale cero. */
interface InvoicingMetrics
{
    /**
     * Total emitido en [from, until) por tipo de documento, en la moneda dada.
     *
     * @return array<string, Money> valor de InvoiceType → total
     */
    public function issuedTotals(CarbonImmutable $from, CarbonImmutable $until, string $currency): array;
}
