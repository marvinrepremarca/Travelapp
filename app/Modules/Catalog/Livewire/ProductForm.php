<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Livewire;

use App\Modules\Catalog\Actions\SaveProductAction;
use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Suppliers\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class ProductForm extends Component
{
    #[Locked]
    public ?string $productUlid = null;

    public string $code = '';

    public string $name = '';

    public string $product_type = '';

    public string $description = '';

    public string $destination_country = '';

    public string $destination_city = '';

    public string $timezone = '';

    public string $duration_minutes = '';

    public string $supplier_id = '';

    public string $currency = '';

    public function mount(?CatalogProduct $product = null): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $this->timezone = config()->string('travel.agency.timezone');
        $this->currency = config()->string('travel.agency.default_currency');

        if (! $product?->exists) {
            return;
        }

        $this->productUlid = $product->ulid;
        $this->fill([
            'code' => $product->code,
            'name' => $product->name,
            'product_type' => $product->product_type->value,
            'description' => (string) $product->description,
            'destination_country' => $product->destination_country,
            'destination_city' => $product->destination_city,
            'timezone' => $product->timezone,
            'duration_minutes' => (string) $product->duration_minutes,
            'supplier_id' => (string) $product->supplier_id,
            'currency' => $product->currency,
        ]);
    }

    public function save(SaveProductAction $save): void
    {
        Gate::authorize('manage', CatalogProduct::class);
        $product = $this->productUlid === null ? null : CatalogProduct::query()->where('ulid', $this->productUlid)->firstOrFail();

        $validated = $this->validate([
            'code' => ['required', 'string', 'max:30', 'alpha_dash', Rule::unique('catalog_products', 'code')->ignore($this->productUlid, 'ulid')],
            'name' => ['required', 'string', 'max:255'],
            'product_type' => ['required', Rule::in(array_map(static fn(ProductType $type): string => $type->value, CatalogProduct::CATALOG_TYPES))],
            'description' => ['nullable', 'string', 'max:5000'],
            'destination_country' => ['required', 'string', 'size:2', 'alpha'],
            'destination_city' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone:all'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
        ], attributes: $this->attributes());

        try {
            $saved = $save->execute(new ProductData(
                code: $validated['code'],
                name: $validated['name'],
                productType: ProductType::from($validated['product_type']),
                destinationCountry: $validated['destination_country'],
                destinationCity: $validated['destination_city'],
                timezone: $validated['timezone'],
                currency: $validated['currency'],
                durationMinutes: $validated['duration_minutes'] === '' ? null : (int) $validated['duration_minutes'],
                supplierId: $validated['supplier_id'] === '' ? null : (int) $validated['supplier_id'],
                description: $validated['description'] ?: null,
            ), $product);
        } catch (CatalogRuleViolation $violation) {
            $this->addError('product_type', $violation->getMessage());

            return;
        }

        session()->flash('status', __('catalog.saved', ['name' => $saved->name]));
        $this->redirectRoute('catalog.show', $saved, navigate: true);
    }

    public function render(): View
    {
        $title = $this->productUlid === null ? __('catalog.create') : __('catalog.edit');

        return view('catalog::livewire.product-form', [
            'types' => CatalogProduct::CATALOG_TYPES,
            'suppliers' => Supplier::query()->where('is_active', true)->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('catalog.fields');
    }
}
