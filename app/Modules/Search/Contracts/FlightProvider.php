<?php

declare(strict_types=1);

namespace App\Modules\Search\Contracts;

use App\Modules\Search\Data\FlightOffer;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Exceptions\ProviderUnavailable;

/**
 * Puerto de vuelos (ADR-0006). Cada proveedor (Duffel, Fake, en el futuro Amadeus Enterprise o NDC directo)
 * tiene un adaptador en Integrations que traduce su API a estos DTOs.
 */
interface FlightProvider extends BookableProvider
{
    /** Etiqueta del contenedor con la que se registran los adaptadores. */
    public const TAG = 'search.flight_providers';

    /**
     * @return list<FlightOffer>
     *
     * @throws ProviderUnavailable timeout, error del proveedor o respuesta inválida
     */
    public function search(FlightSearchCriteria $criteria): array;
}
