<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

/** Datos de contacto con los que se prellena un cliente nuevo. */
final readonly class CustomerPrefill
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $phone,
        public string $email,
    ) {}
}
