<?php

declare(strict_types=1);

namespace App\Modules\Portal\Services;

use App\Modules\Catalog\Data\ShopProduct;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Precio de venta "desde" en la tienda (canal en línea), siempre recalculado en el servidor; null si no se puede tarifar. */
final readonly class ShopPricing
{
    public function __construct(private PriceCalculator $calculator) {}

    public function from(ShopProduct $product, CarbonImmutable $serviceDate, int $seats = 1): ?Money
    {
        if (!$product->fromNetPerAdult instanceof \Brick\Money\Money) {
            return null;
        }

        try {
            return $this->calculator->calculate(new PriceRequest(
                supplierNet: $product->fromNetPerAdult->multipliedBy($seats),
                saleCurrency: config()->string('travel.agency.default_currency'),
                productType: $product->type,
                channel: SalesChannel::Online,
                serviceDate: $serviceDate,
                passengers: $seats,
                destinationCountry: $product->destinationCountry,
            ))->total();
        } catch (BusinessRuleException) {
            return null;
        }
    }
}
