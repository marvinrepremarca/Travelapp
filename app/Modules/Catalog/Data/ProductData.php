<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use App\Modules\Shared\Enums\ProductType;

final readonly class ProductData
{
    public function __construct(
        public string $code,
        public string $name,
        public ProductType $productType,
        public string $destinationCountry,
        public string $destinationCity,
        public string $timezone,
        public string $currency,
        public ?int $durationMinutes = null,
        public ?int $supplierId = null,
        public ?string $description = null,
    ) {}
}
