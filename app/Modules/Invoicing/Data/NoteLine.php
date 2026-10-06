<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Data;

use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Shared\Enums\ProductType;

/** Línea de una nota crédito o débito, ya calculada (valor e IVA en unidades menores). */
final readonly class NoteLine
{
    public function __construct(
        public string $description,
        public InvoiceLineKind $kind,
        public int $amountMinor,
        public int $taxMinor,
        public ?ProductType $productType = null,
        public ?int $sourceLineId = null,
        public ?string $bookingItemUlid = null,
    ) {}
}
