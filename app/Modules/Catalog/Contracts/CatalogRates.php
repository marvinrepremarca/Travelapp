<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\NetPriceQuote;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Costo neto del producto propio o del paquete según la temporada de la fecha de servicio y la edad de cada pasajero. */
interface CatalogRates
{
    /**
     * @param  list<int>  $passengerAgesAtService  edad cumplida de cada pasajero a la fecha del servicio
     *
     * En un paquete, `serviceDate` es el primer día y cada componente se cotiza en su día del itinerario.
     *
     * @throws CatalogRuleViolation producto inactivo, paquete vacío, fecha sin temporada o tipo de pasajero sin tarifa
     */
    public function netPriceFor(string $productUlid, CarbonImmutable $serviceDate, array $passengerAgesAtService): NetPriceQuote;

    /**
     * Precio neto "desde" por adulto: el menor entre las temporadas vigentes o futuras (en un paquete, la suma de sus componentes).
     * Null si no hay tarifa de adulto vigente o futura.
     */
    public function fromPriceFor(string $productUlid, CarbonImmutable $today): ?Money;
}
