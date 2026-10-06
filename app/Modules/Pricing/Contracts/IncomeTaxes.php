<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Contracts;

use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Impuestos vigentes sobre el ingreso propio de la agencia (para cobros fuera de una cotización, p. ej. notas débito). */
interface IncomeTaxes
{
    public function taxOn(Money $agencyIncome, ?ProductType $productType, CarbonImmutable $date): Money;
}
