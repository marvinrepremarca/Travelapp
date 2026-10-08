<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

use Carbon\CarbonImmutable;

/** Cotización enviada a punto de vencer, para el tablero del asesor. */
final readonly class ExpiringQuote
{
    public function __construct(
        public string $ulid,
        public string $number,
        public string $customerName,
        public CarbonImmutable $validUntil,
    ) {}
}
