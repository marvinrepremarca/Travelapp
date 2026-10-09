<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Total cobrado a clientes en un rango, para conciliar contra lo reconocido por Contabilidad (ADR-0007). */
interface CollectionTotals
{
    /** Abonos aprobados menos reembolsos pagados en [from, until), en la moneda dada. */
    public function netCollectedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): Money;
}
