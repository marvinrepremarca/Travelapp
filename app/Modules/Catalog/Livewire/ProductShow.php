<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\AddPackageComponentAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Actions\RemovePackageComponentAction;
use App\Modules\Catalog\Actions\SetProductActiveAction;
use App\Modules\Catalog\Actions\ToggleDepartureAction;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogPackageComponent;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Services\EloquentCatalogRates;
use App\Modules\Shared\Enums\PassengerType;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/** Ficha del producto: temporadas, tarifas y salidas; si es un paquete, sus componentes por día. */
#[Layout('components.layouts.backoffice')]
final class ProductShow extends Component
{
    use WithPagination;

    /** Sin datos sensibles; Livewire lo rehidrata una vez por request. */
    #[Locked]
    public CatalogProduct $product;

    /** @var array<string, string> */
    public array $season = ['name' => '', 'starts_on' => '', 'ends_on' => '', 'adult' => '', 'child' => '', 'infant' => ''];

    /** @var array<string, string> */
    public array $departure = ['service_date' => '', 'starts_at' => '', 'capacity' => ''];

    /** @var array<string, string> */
    public array $component = ['product' => '', 'day_offset' => '0'];

    public function mount(CatalogProduct $product): void
    {
        Gate::authorize('view', $product);
        $this->product = $product;
    }

    public function toggleActive(SetProductActiveAction $setActive): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $product = $this->product();
        $setActive->execute($product, ! $product->is_active);
    }

    public function addSeason(AddSeasonAction $add): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $product = $this->ownProduct();
        $amountRule = ['nullable', 'numeric', 'min:0', 'decimal:0,2'];
        $data = $this->validate([
            'season.name' => ['required', 'string', 'max:100'],
            'season.starts_on' => ['required', 'date'],
            'season.ends_on' => ['required', 'date', 'after_or_equal:season.starts_on'],
            'season.adult' => ['required', ...array_slice($amountRule, 1)],
            'season.child' => $amountRule,
            'season.infant' => $amountRule,
        ], attributes: $this->prefixed('season', 'catalog.season_fields'))['season'];

        $amounts = [];
        foreach (PassengerType::cases() as $type) {
            if (($data[$type->value] ?? '') !== '') {
                $amounts[$type->value] = Money::of((string) $data[$type->value], $product->currency)->getMinorAmount()->toInt();
            }
        }

        try {
            $add->execute($product, $data['name'], CarbonImmutable::parse($data['starts_on']), CarbonImmutable::parse($data['ends_on']), $amounts);
        } catch (CatalogRuleViolation $violation) {
            $this->addError('season.starts_on', $violation->getMessage());

            return;
        }

        $this->reset('season');
    }

    public function addDeparture(AddDepartureAction $add): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $product = $this->ownProduct();
        $data = $this->validate([
            'departure.service_date' => ['required', 'date', 'after_or_equal:today'],
            'departure.starts_at' => ['required', 'date_format:H:i',
                Rule::unique('catalog_departures', 'starts_at')->where('product_id', $product->id)->where('service_date', $this->departure['service_date'])],
            'departure.capacity' => ['required', 'integer', 'min:1', 'max:' . config()->integer('travel.catalog.max_departure_capacity')],
        ], attributes: $this->prefixed('departure', 'catalog.departure_fields'))['departure'];

        $add->execute($product, CarbonImmutable::parse($data['service_date']), $data['starts_at'], (int) $data['capacity']);
        $this->reset('departure');
    }

    public function addComponent(AddPackageComponentAction $add): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $package = $this->product();
        $data = $this->validate([
            'component.product' => ['required', Rule::exists('catalog_products', 'ulid')],
            'component.day_offset' => ['required', 'integer', 'min:0', 'max:' . config()->integer('travel.catalog.max_package_days')],
        ], attributes: $this->prefixed('component', 'catalog.component_fields'))['component'];

        try {
            $add->execute($package, CatalogProduct::query()->where('ulid', $data['product'])->firstOrFail(), (int) $data['day_offset']);
        } catch (CatalogRuleViolation $violation) {
            $this->addError('component.product', $violation->getMessage());

            return;
        }

        $this->reset('component');
    }

    public function removeComponent(string $componentUlid, RemovePackageComponentAction $remove): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $component = CatalogPackageComponent::query()
            ->where('package_id', $this->product()->id)
            ->where('ulid', $componentUlid)
            ->first() ?? abort(404);

        $remove->execute($component);
    }

    public function toggleDeparture(string $departureUlid, ToggleDepartureAction $toggle): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $departure = CatalogDeparture::query()
            ->where('product_id', $this->product()->id)
            ->where('ulid', $departureUlid)
            ->first() ?? abort(404);

        $toggle->execute($departure);
    }

    public function render(MoneyPresenter $presenter, EloquentCatalogRates $rates): View
    {
        $product = $this->product;
        $fromPrice = $rates->fromPriceOf($product, CarbonImmutable::now($product->timezone));

        if ($product->isPackage()) {
            return $this->screen('catalog::livewire.package-show', [
                'product' => $product->load(['components' => static fn($query) => $query->with('component')->orderBy('day_offset')]),
                'fromPrice' => $fromPrice,
                'candidates' => Gate::allows('manage', CatalogProduct::class) ? $this->componentCandidates($product) : [],
            ], $presenter);
        }

        $product->load(['seasons' => static fn($query) => $query->with('rates')->orderBy('starts_on')]);
        $departures = CatalogDeparture::query()
            ->where('product_id', $product->id)
            ->where('service_date', '>=', CarbonImmutable::now($product->timezone)->toDateString())
            ->orderBy('service_date')
            ->orderBy('starts_at')
            ->paginate(config()->integer('travel.catalog.departures_per_page'));

        return $this->screen('catalog::livewire.product-show', [
            'product' => $product,
            'departures' => $departures,
            'fromPrice' => $fromPrice,
            'passengerTypes' => PassengerType::cases(),
        ], $presenter);
    }

    /**
     * @param  view-string  $name
     * @param  array<string, mixed>  $data
     */
    private function screen(string $name, array $data, MoneyPresenter $presenter): View
    {
        return view($name, [...$data, 'presenter' => $presenter, 'canManage' => Gate::allows('manage', CatalogProduct::class)])
            ->title($this->product->name)
            ->layoutData(['heading' => $this->product->name]);
    }

    /**
     * Productos propios activos que pueden entrar al paquete (misma moneda, no paquetes).
     *
     * @return array<string, string>
     */
    private function componentCandidates(CatalogProduct $package): array
    {
        return CatalogProduct::query()
            ->where('is_active', true)
            ->whereIn('product_type', array_map(static fn(ProductType $type): string => $type->value, CatalogProduct::OWN_PRODUCT_TYPES))
            ->where('currency', $package->currency)
            ->orderBy('name')
            ->pluck('name', 'ulid')
            ->all();
    }

    /** Temporadas y salidas son de productos sueltos; el paquete se arma con sus componentes. */
    private function ownProduct(): CatalogProduct
    {
        return $this->product->isPackage() ? abort(404) : $this->product;
    }

    /** @return array<string, string> */
    private function prefixed(string $prefix, string $translationKey): array
    {
        /** @var array<string, string> $labels */
        $labels = trans($translationKey);

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["{$prefix}.{$field}" => $label])->all();
    }

    private function product(): CatalogProduct
    {
        return $this->product;
    }
}
