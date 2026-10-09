<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Venta que no pasó por el sistema (p. ej. una venta externa o anterior a la puesta en marcha). */
final readonly class ManualRevenueData
{
    public function __construct(
        public string $description,
        public ?string $customerName,
        public Money $amount,
        public CarbonImmutable $recognizedOn,
    ) {}
}
