<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Livewire;

use App\Modules\Pricing\Enums\FeeBasis;
use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Pricing\Models\FeeRule;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Pricing\Models\TaxRule;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Reglas de precio: markups, fees de servicio e impuestos, con vigencia. Se desactivan, no se borran. */
#[Layout('components.layouts.backoffice')]
final class PricingRulesManager extends Component
{
    public const TAB_MARKUPS = 'markups';

    public const TAB_FEES = 'fees';

    public const TAB_TAXES = 'taxes';

    #[Url(except: self::TAB_MARKUPS)]
    public string $tab = self::TAB_MARKUPS;

    /** @var array<string, string> */
    public array $markup = ['name' => '', 'product_type' => '', 'supplier_id' => '', 'destination_country' => '', 'sales_channel' => '', 'kind' => 'percentage', 'value' => '', 'currency' => 'COP', 'min_margin' => '', 'priority' => '0', 'valid_from' => '', 'valid_until' => ''];

    /** @var array<string, string> */
    public array $fee = ['name' => '', 'product_type' => '', 'sales_channel' => '', 'basis' => 'per_booking', 'amount' => '', 'currency' => 'COP', 'valid_from' => '', 'valid_until' => ''];

    /** @var array<string, mixed> */
    public array $tax = ['name' => '', 'rate' => '', 'exempt' => [], 'valid_from' => '', 'valid_until' => ''];

    public function mount(): void
    {
        Gate::authorize(Permission::PricingManage->value);
    }

    public function addMarkup(): void
    {
        Gate::authorize(Permission::PricingManage->value);
        $isPercentage = $this->markup['kind'] === MarkupKind::Percentage->value;

        $data = $this->validate([
            'markup.name' => ['required', 'string', 'max:255'],
            'markup.product_type' => ['nullable', Rule::enum(ProductType::class)],
            'markup.supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'markup.destination_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'markup.sales_channel' => ['nullable', Rule::enum(SalesChannel::class)],
            'markup.kind' => ['required', Rule::enum(MarkupKind::class)],
            'markup.value' => ['required', 'numeric', 'min:0', $isPercentage ? 'max:1000' : 'max:999999999', 'decimal:0,2'],
            'markup.currency' => [$isPercentage ? 'nullable' : 'required', 'string', 'size:3', 'alpha'],
            'markup.min_margin' => ['nullable', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'markup.priority' => ['required', 'integer', 'min:-100', 'max:100'],
            'markup.valid_from' => ['required', 'date'],
            'markup.valid_until' => ['nullable', 'date', 'after_or_equal:markup.valid_from'],
        ], attributes: $this->prefixed('markup'))['markup'];

        $currency = mb_strtoupper((string) $data['currency']);

        MarkupRule::query()->create([
            'name' => $data['name'],
            'product_type' => $data['product_type'] ?: null,
            'supplier_id' => $data['supplier_id'] ?: null,
            'destination_country' => $data['destination_country'] ? mb_strtoupper($data['destination_country']) : null,
            'sales_channel' => $data['sales_channel'] ?: null,
            'kind' => $data['kind'],
            'rate_basis_points' => $isPercentage ? Percentage::fromString((string) $data['value'])->basisPoints : null,
            'amount_minor' => $isPercentage ? null : Money::of((string) $data['value'], $currency)->getMinorAmount()->toInt(),
            'currency' => $isPercentage ? null : $currency,
            'min_margin_basis_points' => $data['min_margin'] !== '' && $data['min_margin'] !== null ? Percentage::fromString((string) $data['min_margin'])->basisPoints : null,
            'priority' => (int) $data['priority'],
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('markup');
    }

    public function addFee(): void
    {
        Gate::authorize(Permission::PricingManage->value);

        $data = $this->validate([
            'fee.name' => ['required', 'string', 'max:255'],
            'fee.product_type' => ['nullable', Rule::enum(ProductType::class)],
            'fee.sales_channel' => ['nullable', Rule::enum(SalesChannel::class)],
            'fee.basis' => ['required', Rule::enum(FeeBasis::class)],
            'fee.amount' => ['required', 'numeric', 'gt:0', 'max:999999999', 'decimal:0,2'],
            'fee.currency' => ['required', 'string', 'size:3', 'alpha'],
            'fee.valid_from' => ['required', 'date'],
            'fee.valid_until' => ['nullable', 'date', 'after_or_equal:fee.valid_from'],
        ], attributes: $this->prefixed('fee'))['fee'];

        $currency = mb_strtoupper((string) $data['currency']);

        FeeRule::query()->create([
            'name' => $data['name'],
            'product_type' => $data['product_type'] ?: null,
            'sales_channel' => $data['sales_channel'] ?: null,
            'basis' => $data['basis'],
            'amount_minor' => Money::of((string) $data['amount'], $currency)->getMinorAmount()->toInt(),
            'currency' => $currency,
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('fee');
    }

    public function addTax(): void
    {
        Gate::authorize(Permission::PricingManage->value);

        $data = $this->validate([
            'tax.name' => ['required', 'string', 'max:255'],
            'tax.rate' => ['required', 'numeric', 'gt:0', 'max:100', 'decimal:0,2'],
            'tax.exempt' => ['array'],
            'tax.exempt.*' => [Rule::enum(ProductType::class)],
            'tax.valid_from' => ['required', 'date'],
            'tax.valid_until' => ['nullable', 'date', 'after_or_equal:tax.valid_from'],
        ], attributes: $this->prefixed('tax'))['tax'];

        TaxRule::query()->create([
            'name' => $data['name'],
            'rate_basis_points' => Percentage::fromString((string) $data['rate'])->basisPoints,
            'exempt_product_types' => array_values($data['exempt'] ?? []),
            'valid_from' => $data['valid_from'],
            'valid_until' => $data['valid_until'] ?: null,
            'is_active' => true,
        ]);

        $this->reset('tax');
    }

    public function toggle(string $type, string $ulid): void
    {
        Gate::authorize(Permission::PricingManage->value);

        $model = match ($type) {
            self::TAB_MARKUPS => MarkupRule::query(),
            self::TAB_FEES => FeeRule::query(),
            self::TAB_TAXES => TaxRule::query(),
            default => abort(404),
        };

        $rule = $model->where('ulid', $ulid)->first() ?? abort(404);
        $rule->setAttribute('is_active', ! $rule->getAttribute('is_active'));
        $rule->save();
    }

    public function render(): View
    {
        return view('pricing::livewire.pricing-rules-manager', [
            'markups' => MarkupRule::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'fees' => FeeRule::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'taxes' => TaxRule::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'suppliers' => Supplier::query()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
            'productTypes' => ProductType::cases(),
            'channels' => SalesChannel::cases(),
            'kinds' => MarkupKind::cases(),
            'bases' => FeeBasis::cases(),
            'presenter' => app(MoneyPresenter::class),
        ])->title(__('pricing.rules.title'))
            ->layoutData(['heading' => __('pricing.rules.title')]);
    }

    /** @return array<string, string> */
    private function prefixed(string $prefix): array
    {
        /** @var array<string, string> $labels */
        $labels = trans("pricing.rules.fields.{$prefix}");

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["{$prefix}.{$field}" => $label])->all();
    }
}
