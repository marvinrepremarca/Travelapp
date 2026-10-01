<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Contracts;

use App\Modules\Pricing\Data\PriceBreakdown;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;

/** Calcula el precio de venta en el servidor. Ningún precio enviado por el cliente se usa. */
interface PriceCalculator
{
    /** @throws ExchangeRateUnavailable */
    public function calculate(PriceRequest $request): PriceBreakdown;
}
