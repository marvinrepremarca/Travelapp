<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

use App\Modules\Pricing\Enums\ExchangeRateSource;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;

/**
 * Tasa aplicada en una conversión, con su origen: se congela en la cotización y en la reserva.
 * `officialRate` es la tasa de la fuente; `rate` incluye el spread de la agencia.
 */
final readonly class ExchangeRateQuote
{
    public function __construct(
        public string $from,
        public string $to,
        public BigDecimal $rate,
        public BigDecimal $officialRate,
        public int $spreadBasisPoints,
        public ExchangeRateSource $source,
        public CarbonImmutable $rateDate,
    ) {}
}
