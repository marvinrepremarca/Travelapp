<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Contracts;

use App\Modules\Pricing\Data\OfficialRate;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use Carbon\CarbonImmutable;

/** Puerto: fuente oficial de tasas (hoy la TRM). El adaptador vive en el módulo Integrations. */
interface OfficialExchangeRateSource
{
    /** @throws ExchangeRateUnavailable */
    public function rateOn(CarbonImmutable $date): OfficialRate;
}
