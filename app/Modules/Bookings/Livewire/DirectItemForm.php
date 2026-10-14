<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Livewire;

use App\Modules\Bookings\Actions\AddDirectItemAction;
use App\Modules\Bookings\Data\DirectItemData;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Agrega servicios a un expediente directo; el precio de venta lo calcula el servidor (ADR-0007). */
#[Layout('components.layouts.backoffice')]
final class DirectItemForm extends Component
{
    private const AGES_SEPARATOR = ',';

    private const AGES_PATTERN = '/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/';

    #[Locked]
    public string $bookingUlid = '';

    /** @var array{description: string, product_type: string, supplier: string, destination_country: string, service_date: string, nights: string, ages: string, net_amount: string, net_currency: string, channel: string} */
    public array $item = [
        'description' => '', 'product_type' => '', 'supplier' => '', 'destination_country' => '', 'service_date' => '',
        'nights' => '0', 'ages' => '', 'net_amount' => '', 'net_currency' => '', 'channel' => '',
    ];

    /** Producto propio del catálogo (opcional): llena los datos del servicio y su neto sale de las tarifas por temporada. */
    public string $catalogProduct = '';

    public function mount(Booking $booking): void
    {
        Gate::authorize('update', $booking);
        $this->bookingUlid = $booking->ulid;
    }

    public function updatedCatalogProduct(string $ulid): void
    {
        $product = $ulid === '' ? null : $this->catalogProducts()->where('ulid', $ulid)->first();
        if (! $product instanceof CatalogProduct) {
            return;
        }

        $this->item = [...$this->item,
            'description' => $product->name,
            'product_type' => $product->product_type->value,
            'destination_country' => $product->destination_country,
            'supplier' => (string) $product->supplier_id,
            'net_currency' => $product->currency,
        ];
    }

    /** Neto del proveedor según la temporada de la fecha del servicio y la edad de cada pasajero. */
    public function priceFromCatalog(CatalogRates $rates): void
    {
        $this->resetValidation();
        $validated = $this->validate([
            'catalogProduct' => ['required', Rule::exists('catalog_products', 'ulid')->where('is_active', true)],
            'item.service_date' => ['required', 'date'],
            'item.ages' => ['required', 'string', 'regex:' . self::AGES_PATTERN],
        ], attributes: [...Arr::dot(trans('bookings.direct.item_fields')), 'catalogProduct' => __('bookings.direct.catalog_product')]);

        try {
            $quote = $rates->netPriceFor($validated['catalogProduct'], CarbonImmutable::parse($validated['item']['service_date']), $this->ages($validated['item']['ages']));
        } catch (BusinessRuleException $violation) {
            $this->addError('catalogProduct', $violation->getMessage());

            return;
        }

        $this->item['net_amount'] = (string) $quote->total->getAmount();
        $this->item['net_currency'] = $quote->total->getCurrency()->getCurrencyCode();
    }

    public function add(AddDirectItemAction $add): void
    {
        $booking = $this->booking();
        Gate::authorize('update', $booking);
        $validated = $this->validate([
            'item.description' => ['required', 'string', 'max:255'],
            'item.product_type' => ['required', Rule::enum(ProductType::class)],
            'item.supplier' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'item.destination_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'item.service_date' => ['required', 'date'],
            'item.nights' => ['required', 'integer', 'min:0'],
            'item.ages' => ['required', 'string', 'regex:' . self::AGES_PATTERN],
            'item.net_amount' => ['required', 'numeric', 'gt:0'],
            'item.net_currency' => ['required', 'string', 'size:3', 'alpha'],
            'item.channel' => ['required', Rule::enum(SalesChannel::class)],
        ], attributes: Arr::dot(trans('bookings.direct.item_fields')));
        $data = $validated['item'];

        try {
            $add->execute($booking, new DirectItemData(
                description: $data['description'],
                productType: ProductType::from($data['product_type']),
                supplierId: $data['supplier'] !== '' && $data['supplier'] !== null ? (int) $data['supplier'] : null,
                destinationCountry: $data['destination_country'] ? mb_strtoupper($data['destination_country']) : null,
                serviceDate: CarbonImmutable::parse($data['service_date']),
                nights: (int) $data['nights'],
                passengerAges: $this->ages($data['ages']),
                supplierNet: Money::of($data['net_amount'], mb_strtoupper($data['net_currency'])),
                channel: SalesChannel::from($data['channel']),
            ), CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('item.net_amount', $violation->getMessage());

            return;
        }

        session()->flash('status', __('bookings.direct.item_added', ['service' => $data['description']]));
        $this->redirectRoute('bookings.show', $booking, navigate: true);
    }

    public function render(): View
    {
        $booking = $this->booking();
        $title = __('bookings.direct.add_item_title', ['number' => $booking->number]);

        return view('bookings::livewire.direct-item-form', [
            'booking' => $booking,
            'productTypes' => ProductType::cases(),
            'channels' => SalesChannel::cases(),
            'suppliers' => Supplier::query()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
            'catalogProducts' => $this->catalogEnabled() ? $this->catalogProducts()->orderBy('name')->pluck('name', 'ulid')->all() : [],
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return list<int> */
    private function ages(string $ages): array
    {
        return array_map(intval(...), array_map(trim(...), explode(self::AGES_SEPARATOR, $ages)));
    }

    private function catalogEnabled(): bool
    {
        return app(Capabilities::class)->enabled(Capability::OwnProduct);
    }

    /** @return Builder<CatalogProduct> */
    private function catalogProducts(): Builder
    {
        return $this->catalogEnabled() ? CatalogProduct::query()->where('is_active', true) : CatalogProduct::query()->whereRaw('1 = 0');
    }

    private function booking(): Booking
    {
        return Booking::query()->where('ulid', $this->bookingUlid)->firstOrFail();
    }
}
