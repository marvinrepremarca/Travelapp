<?php

declare(strict_types=1);

namespace App\Modules\Portal\Data;

/** Solicitud de reserva desde la tienda: salida elegida, cupos y contacto (con consentimiento de tratamiento de datos). */
final readonly class ShopRequest
{
    public function __construct(
        public string $productUlid,
        public string $departureUlid,
        public int $seats,
        public string $contactName,
        public string $email,
        public string $phone,
        public bool $acceptsDataProcessing,
    ) {}
}
