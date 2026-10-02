<?php

declare(strict_types=1);

namespace App\Modules\Organization\Data;

/** Membrete: identidad legal, contacto, RNT y logo incrustado (data URI) para no depender de recursos remotos. */
final readonly class Letterhead
{
    public function __construct(
        public string $tradeName,
        public ?string $legalName,
        public ?string $nit,
        public ?string $rntNumber,
        public ?string $address,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public ?string $logoDataUri,
        public ?string $primaryColor,
    ) {}
}
