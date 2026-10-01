<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Data;

use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Enums\CommissionBase;

/** Comisión vigente que otros módulos (precios, finanzas) usan para calcular. */
final readonly class CommissionTerm
{
    public function __construct(
        public Percentage $rate,
        public CommissionBase $base,
    ) {}
}
