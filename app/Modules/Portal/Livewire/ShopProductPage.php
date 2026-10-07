<?php

declare(strict_types=1);

namespace App\Modules\Portal\Livewire;

use App\Modules\Catalog\Contracts\ShopCatalog;
use App\Modules\Catalog\Data\ShopProduct;
use App\Modules\Portal\Actions\RequestShopBookingAction;
use App\Modules\Portal\Data\ShopRequest;
use App\Modules\Portal\Services\ShopPricing;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Ficha del producto en la tienda: elegir salida y cupos y dejar los datos de contacto para que un asesor confirme. */
#[Layout('components.layouts.portal')]
final class ShopProductPage extends Component
{
    private const LIMITER = 'portal-shop:';

    private const SECONDS_PER_HOUR = 3600;

    #[Locked]
    public string $productUlid = '';

    public string $departure = '';

    public string $seats = '1';

    public string $contactName = '';

    public string $email = '';

    public string $phone = '';

    public bool $acceptsDataProcessing = false;

    public bool $requested = false;

    public function mount(string $product, ShopCatalog $catalog): void
    {
        $shopProduct = $this->find($catalog, $product) ?? abort(404);
        $this->productUlid = $shopProduct->ulid;
        $this->departure = $shopProduct->departures[0]->ulid ?? '';
    }

    public function request(RequestShopBookingAction $request): void
    {
        $this->validate([
            'departure' => ['required', 'string', 'max:30'],
            'seats' => ['required', 'integer', 'min:1', 'max:' . config()->integer('travel.portal.shop_max_seats')],
            'contactName' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ()-]{7,20}$/'],
            'acceptsDataProcessing' => ['accepted'],
        ], attributes: __('portal.shop.fields'));

        $key = self::LIMITER . request()->ip();
        if (RateLimiter::tooManyAttempts($key, config()->integer('travel.portal.shop_requests_per_hour'))) {
            $this->addError('request', __('portal.shop.errors.too_many'));

            return;
        }
        RateLimiter::hit($key, self::SECONDS_PER_HOUR);

        try {
            $request->execute(new ShopRequest(
                $this->productUlid,
                $this->departure,
                (int) $this->seats,
                $this->contactName,
                $this->email,
                $this->phone,
                $this->acceptsDataProcessing,
            ), $this->today());
        } catch (BusinessRuleException $violation) {
            $this->addError('request', $violation->getMessage());

            return;
        }

        $this->reset('contactName', 'email', 'phone', 'acceptsDataProcessing');
        $this->requested = true;
    }

    public function render(ShopCatalog $catalog, ShopPricing $pricing, MoneyPresenter $presenter): View
    {
        $product = $this->find($catalog, $this->productUlid) ?? abort(404);
        $selected = $product->departure($this->departure) ?? $product->departures[0];
        $seats = max(1, min((int) $this->seats, config()->integer('travel.portal.shop_max_seats')));
        $price = $pricing->from($product, $selected->serviceDate, $seats);

        return view('portal::livewire.shop-product', [
            'product' => $product,
            'estimate' => $price instanceof \Brick\Money\Money ? $presenter->format($price) : null,
            'seatsCount' => $seats,
        ])->title($product->name);
    }

    private function find(ShopCatalog $catalog, string $productUlid): ?ShopProduct
    {
        $today = $this->today();

        return $catalog->product($productUlid, $today, $today->addDays(config()->integer('travel.portal.shop_window_days')));
    }

    private function today(): CarbonImmutable
    {
        return CarbonImmutable::now(config()->string('travel.agency.timezone'))->startOfDay();
    }
}
