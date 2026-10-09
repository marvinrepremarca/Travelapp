<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Obligación con un proveedor que no nace de un expediente (p. ej. un servicio contratado por fuera del sistema). */
final readonly class ManualPayableData
{
    public function __construct(
        public int $supplierId,
        public string $description,
        public Money $amount,
        public CarbonImmutable $dueDate,
    ) {}
}
