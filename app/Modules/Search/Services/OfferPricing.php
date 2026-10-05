<?php

declare(strict_types=1);

namespace App\Modules\Search\Services;

use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Precio de venta de una oferta de proveedor con las reglas de Pricing; null si falta la tasa de cambio. */
final readonly class OfferPricing
{
    public function __construct(private PriceCalculator $calculator) {}

    public function sale(Money $net, ProductType $type, CarbonImmutable $serviceDate, int $passengers, int $nights, ?string $destinationCountry = null): ?Money
    {
        try {
            return $this->calculator->calculate(new PriceRequest(
                supplierNet: $net,
                saleCurrency: config()->string('travel.agency.default_currency'),
                productType: $type,
                channel: SalesChannel::Branch,
                serviceDate: $serviceDate,
                passengers: $passengers,
                nights: $nights,
                destinationCountry: $destinationCountry,
            ))->total();
        } catch (BusinessRuleException) {
            return null;
        }
    }
}
