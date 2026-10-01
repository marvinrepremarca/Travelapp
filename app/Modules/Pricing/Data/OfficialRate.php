<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;

/** Tasa entregada por una fuente oficial para un rango de vigencia (fines de semana y festivos cubren varios días). */
final readonly class OfficialRate
{
    public function __construct(
        public string $base,
        public string $quote,
        public BigDecimal $rate,
        public CarbonImmutable $validFrom,
        public CarbonImmutable $validUntil,
    ) {}
}
