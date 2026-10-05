<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

/**
 * Resultado agregado: ofertas de los proveedores que respondieron, ordenadas por precio,
 * y la lista de los que fallaron o están en pausa (resultados parciales, nunca error total).
 */
final readonly class SearchResult
{
    /**
     * @param  list<FlightOffer|HotelOffer>  $offers
     * @param  list<string>  $unavailableProviders
     */
    public function __construct(
        public array $offers,
        public array $unavailableProviders,
    ) {}
}
