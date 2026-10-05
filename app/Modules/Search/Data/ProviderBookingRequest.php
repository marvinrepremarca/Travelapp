<?php

declare(strict_types=1);

namespace App\Modules\Search\Data;

/** Lo mínimo para reservar con un proveedor; sin documentos ni datos de pago (los gestiona cada adaptador con tokens). */
final readonly class ProviderBookingRequest
{
    /**
     * @param  list<string>  $passengerNames
     */
    public function __construct(
        public string $offerId,
        public string $idempotencyKey,
        public array $passengerNames,
        public string $agencyReference,
    ) {}
}
