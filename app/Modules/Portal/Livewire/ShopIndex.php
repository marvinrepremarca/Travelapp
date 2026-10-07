<?php

declare(strict_types=1);

namespace App\Modules\Portal\Livewire;

use App\Modules\Catalog\Contracts\ShopCatalog;
use App\Modules\Catalog\Data\ShopProduct;
use App\Modules\Portal\Services\ShopPricing;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Tienda B2C: tours, pasadías y actividades propias con salidas abiertas y precio "desde". */
#[Layout('components.layouts.portal')]
final class ShopIndex extends Component
{
    public function render(ShopCatalog $catalog, ShopPricing $pricing, MoneyPresenter $presenter): View
    {
        $today = CarbonImmutable::now(config()->string('travel.agency.timezone'))->startOfDay();
        $products = $catalog->available($today, $today->addDays(config()->integer('travel.portal.shop_window_days')));

        return view('portal::livewire.shop-index', [
            'products' => $products,
            'prices' => collect($products)->mapWithKeys(static function (ShopProduct $product) use ($pricing, $presenter, $today): array {
                $price = $pricing->from($product, $product->departures[0]->serviceDate ?? $today);

                return [$product->ulid => $price instanceof \Brick\Money\Money ? $presenter->format($price) : null];
            })->all(),
        ])->title(__('portal.shop.title'));
    }
}
