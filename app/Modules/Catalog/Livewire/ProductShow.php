<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Actions\SetProductActiveAction;
use App\Modules\Catalog\Actions\ToggleDepartureAction;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Shared\Enums\PassengerType;
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

/** Ficha del producto: temporadas con costo neto por tipo de pasajero y salidas con su cupo. */
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
        $amountRule = ['nullable', 'numeric', 'min:0', 'decimal:0,2'];
        $data = $this->validate([
            'season.name' => ['required', 'string', 'max:100'],
            'season.starts_on' => ['required', 'date'],
            'season.ends_on' => ['required', 'date', 'after_or_equal:season.starts_on'],
            'season.adult' => ['required', ...array_slice($amountRule, 1)],
            'season.child' => $amountRule,
            'season.infant' => $amountRule,
        ], attributes: $this->prefixed('season', 'catalog.season_fields'))['season'];

        $product = $this->product();
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
        $product = $this->product();
        $data = $this->validate([
            'departure.service_date' => ['required', 'date', 'after_or_equal:today'],
            'departure.starts_at' => ['required', 'date_format:H:i',
                Rule::unique('catalog_departures', 'starts_at')->where('product_id', $product->id)->where('service_date', $this->departure['service_date'])],
            'departure.capacity' => ['required', 'integer', 'min:1', 'max:' . config()->integer('travel.catalog.max_departure_capacity')],
        ], attributes: $this->prefixed('departure', 'catalog.departure_fields'))['departure'];

        $add->execute($product, CarbonImmutable::parse($data['service_date']), $data['starts_at'], (int) $data['capacity']);
        $this->reset('departure');
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

    public function render(MoneyPresenter $presenter): View
    {
        $product = $this->product->load(['seasons' => static fn($query) => $query->with('rates')->orderBy('starts_on')]);

        $departures = CatalogDeparture::query()
            ->where('product_id', $product->id)
            ->where('service_date', '>=', CarbonImmutable::now($product->timezone)->toDateString())
            ->orderBy('service_date')
            ->orderBy('starts_at')
            ->paginate(config()->integer('travel.catalog.departures_per_page'));

        return view('catalog::livewire.product-show', [
            'product' => $product,
            'departures' => $departures,
            'passengerTypes' => PassengerType::cases(),
            'presenter' => $presenter,
            'canManage' => Gate::allows('manage', CatalogProduct::class),
        ])->title($product->name)
            ->layoutData(['heading' => $product->name]);
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
