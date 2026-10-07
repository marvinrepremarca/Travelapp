<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Contracts\ShopCatalog;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Catalog\Data\ShopProduct;
use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Catalog\Models\CatalogProduct;
use Carbon\CarbonImmutable;

final readonly class EloquentShopCatalog implements ShopCatalog
{
    public function __construct(private DepartureSchedule $schedule, private CatalogRates $rates) {}

    public function available(CarbonImmutable $from, CarbonImmutable $until): array
    {
        $byProduct = [];
        foreach ($this->schedule->between($from, $until) as $departure) {
            if ($departure->status === DepartureStatus::Open && $departure->capacity > $departure->reservedSeats) {
                $byProduct[$departure->productUlid][] = $departure;
            }
        }
        if ($byProduct === []) {
            return [];
        }

        $products = CatalogProduct::query()
            ->where('is_active', true)
            ->whereIn('ulid', array_keys($byProduct))
            ->orderBy('name')
            ->limit(config()->integer('travel.portal.shop_max_products'))
            ->get(['id', 'ulid', 'name', 'product_type', 'description', 'destination_city', 'destination_country']);

        return array_values($products->map(fn(CatalogProduct $product): ShopProduct => $this->toShop($product, $byProduct[$product->ulid], $from))->all());
    }

    public function product(string $productUlid, CarbonImmutable $from, CarbonImmutable $until): ?ShopProduct
    {
        foreach ($this->available($from, $until) as $product) {
            if ($product->ulid === $productUlid) {
                return $product;
            }
        }

        return null;
    }

    /** @param  list<ScheduledDeparture>  $departures */
    private function toShop(CatalogProduct $product, array $departures, CarbonImmutable $today): ShopProduct
    {
        return new ShopProduct(
            $product->ulid,
            $product->name,
            $product->product_type,
            $product->description,
            $product->destination_city,
            $product->destination_country,
            $this->rates->fromPriceFor($product->ulid, $today),
            $departures,
        );
    }
}
