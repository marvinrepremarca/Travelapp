<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Lo necesario para calcular el precio de venta de un servicio. */
final readonly class PriceRequest
{
    public function __construct(
        public Money $supplierNet,
        public string $saleCurrency,
        public ProductType $productType,
        public SalesChannel $channel,
        public CarbonImmutable $serviceDate,
        public int $passengers = 1,
        public int $nights = 0,
        public ?int $supplierId = null,
        public ?string $destinationCountry = null,
    ) {}
}
