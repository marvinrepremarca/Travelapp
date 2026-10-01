<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Contracts;

use App\Modules\Pricing\Data\ExchangeRateQuote;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Conversión de monedas con la tasa de la agencia vigente en una fecha. */
interface ExchangeRates
{
    /** @throws ExchangeRateUnavailable */
    public function quote(string $from, string $to, CarbonImmutable $date): ExchangeRateQuote;

    /**
     * @return array{0: Money, 1: ExchangeRateQuote}
     *
     * @throws ExchangeRateUnavailable
     */
    public function convert(Money $amount, string $to, CarbonImmutable $date): array;
}
