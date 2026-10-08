<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

/** Contacto de WhatsApp de un cliente que autorizó el tratamiento de sus datos. */
final readonly class CustomerWhatsApp
{
    public function __construct(
        public int $customerId,
        public string $name,
        public string $phone,
    ) {}
}
