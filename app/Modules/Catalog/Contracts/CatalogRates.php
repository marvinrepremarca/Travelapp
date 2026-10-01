<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\NetPriceQuote;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use Carbon\CarbonImmutable;

/** Costo neto del producto propio según la temporada de la fecha de servicio y la edad de cada pasajero. */
interface CatalogRates
{
    /**
     * @param  list<int>  $passengerAgesAtService  edad cumplida de cada pasajero a la fecha del servicio
     *
     * @throws CatalogRuleViolation producto inactivo, fecha sin temporada o tipo de pasajero sin tarifa
     */
    public function netPriceFor(string $productUlid, CarbonImmutable $serviceDate, array $passengerAgesAtService): NetPriceQuote;
}
