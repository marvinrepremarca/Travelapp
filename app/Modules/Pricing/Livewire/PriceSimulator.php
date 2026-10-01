<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Livewire;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceBreakdown;
use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Pricing\Exceptions\ExchangeRateUnavailable;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Calculadora para probar las reglas: muestra el desglose; el margen solo a quien puede verlo. */
#[Layout('components.layouts.backoffice')]
final class PriceSimulator extends Component
{
    public string $net = '';

    public string $net_currency = 'COP';

    public string $sale_currency = 'COP';

    public string $product_type = 'hotel';

    public string $sales_channel = 'branch';

    public string $supplier_id = '';

    public string $destination_country = '';

    public string $service_date = '';

    public string $passengers = '1';

    public string $nights = '0';

    /** @var list<array{type: string, description: string, amount: string}> */
    public array $lines = [];

    public string $total = '';

    public string $margin = '';

    public string $rateInfo = '';

    public function mount(): void
    {
        $this->service_date = CarbonImmutable::today()->toDateString();
    }

    public function calculate(PriceCalculator $calculator, MoneyPresenter $presenter): void
    {
        $data = $this->validate([
            'net' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'net_currency' => ['required', 'string', 'size:3', 'alpha'],
            'sale_currency' => ['required', 'string', 'size:3', 'alpha'],
            'product_type' => ['required', Rule::enum(ProductType::class)],
            'sales_channel' => ['required', Rule::enum(SalesChannel::class)],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'destination_country' => ['nullable', 'string', 'size:2', 'alpha'],
            'service_date' => ['required', 'date'],
            'passengers' => ['required', 'integer', 'min:1', 'max:' . config()->integer('travel.crm.max_lead_travelers')],
            'nights' => ['required', 'integer', 'min:0', 'max:365'],
        ], attributes: $this->attributes());

        try {
            $breakdown = $calculator->calculate(new PriceRequest(
                supplierNet: Money::of((string) $data['net'], mb_strtoupper($data['net_currency'])),
                saleCurrency: mb_strtoupper($data['sale_currency']),
                productType: ProductType::from($data['product_type']),
                channel: SalesChannel::from($data['sales_channel']),
                serviceDate: CarbonImmutable::parse($data['service_date']),
                passengers: (int) $data['passengers'],
                nights: (int) $data['nights'],
                supplierId: $data['supplier_id'] ? (int) $data['supplier_id'] : null,
                destinationCountry: $data['destination_country'] ?: null,
            ));
        } catch (ExchangeRateUnavailable $exception) {
            $this->addError('net_currency', $exception->getMessage());

            return;
        }

        $this->present($breakdown, $presenter);
    }

    public function render(): View
    {
        return view('pricing::livewire.price-simulator', [
            'productTypes' => ProductType::cases(),
            'channels' => SalesChannel::cases(),
            'suppliers' => Supplier::query()->orderBy('trade_name')->pluck('trade_name', 'id')->all(),
            // El margen se oculta a quien no tiene el permiso, salvo que la agencia decida mostrarlo a todos ⚙.
            'canSeeMargin' => Auth::user()?->can(Permission::MarginsView->value) === true || ! app(AppSettings::class)->hideMarginsFromAgents(),
        ])->title(__('pricing.simulator.title'))
            ->layoutData(['heading' => __('pricing.simulator.title')]);
    }

    private function present(PriceBreakdown $breakdown, MoneyPresenter $presenter): void
    {
        $this->lines = array_map(static fn(\App\Modules\Pricing\Data\PriceComponent $component): array => [
            'type' => $component->type->label(),
            'description' => $component->description,
            'amount' => $presenter->format($component->amount),
        ], $breakdown->components);

        $this->total = $presenter->format($breakdown->total());
        $this->margin = $presenter->format($breakdown->margin());
        $this->rateInfo = $breakdown->exchangeRate instanceof \App\Modules\Pricing\Data\ExchangeRateQuote ? __('pricing.simulator.rate_info', [
            'from' => $breakdown->exchangeRate->from,
            'to' => $breakdown->exchangeRate->to,
            'rate' => (string) $breakdown->exchangeRate->rate,
            'official' => (string) $breakdown->exchangeRate->officialRate,
            'date' => $breakdown->exchangeRate->rateDate->toDateString(),
        ]) : '';
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> */
        return trans('pricing.simulator.fields');
    }
}
