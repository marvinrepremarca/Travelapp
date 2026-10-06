<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

use Carbon\CarbonImmutable;

/** Lo necesario para avisarle al cliente que tiene una cotización: su enlace público firmado y su vigencia. */
final readonly class QuoteShare
{
    public function __construct(
        public string $quoteUlid,
        public string $number,
        public string $title,
        public int $customerId,
        public int $ownerId,
        public ?int $branchId,
        public string $url,
        public CarbonImmutable $validUntil,
    ) {}
}
