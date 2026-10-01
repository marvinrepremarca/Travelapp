<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Livewire;

use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\AddOptionAction;
use App\Modules\Quotes\Actions\CancelQuoteAction;
use App\Modules\Quotes\Actions\RemoveItemAction;
use App\Modules\Quotes\Actions\RemoveOptionAction;
use App\Modules\Quotes\Actions\ReviseQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Models\QuoteOption;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Ficha de la cotización: opciones con ítems (precio del servidor), envío por versiones, aceptación y cancelación. */
#[Layout('components.layouts.backoffice')]
final class QuoteShow extends Component
{
    private const AGES_SEPARATOR = ',';

    #[Locked]
    public Quote $quote;

    public string $optionUlid = '';

    /** @var array<string, string> */
    public array $item = [
        'kind' => 'catalog', 'catalog_product' => '', 'product_type' => '', 'description' => '', 'net_amount' => '', 'net_currency' => '',
        'supplier_id' => '', 'destination_country' => '', 'service_date' => '', 'nights' => '0', 'ages' => '',
    ];

    public string $newOptionTitle = '';

    /** @var array<string, string> */
    public array $acceptance = ['option' => '', 'note' => ''];

    public function mount(Quote $quote): void
    {
        Gate::authorize('view', $quote);
        $this->quote = $quote;
        $this->optionUlid = (string) $quote->options()->value('ulid');
        $this->item['net_currency'] = $quote->sale_currency;
    }

    public function addOption(AddOptionAction $add): void
    {
        $this->authorizeUpdate();
        $data = $this->validate(['newOptionTitle' => ['required', 'string', 'max:255']], attributes: ['newOptionTitle' => __('quotes.fields.option_title')]);

        $this->attempt('newOptionTitle', function () use ($add, $data): void {
            $this->optionUlid = $add->execute($this->quote, $data['newOptionTitle'])->ulid;
            $this->reset('newOptionTitle');
        });
    }

    public function removeOption(string $ulid, RemoveOptionAction $remove): void
    {
        $this->authorizeUpdate();
        $option = $this->option($ulid);

        $this->attempt('option', function () use ($remove, $option): void {
            $remove->execute($this->quote, $option);
            $this->optionUlid = (string) $this->quote->options()->value('ulid');
        });
    }

    public function addItem(AddItemAction $add): void
    {
        $this->authorizeUpdate();
        $option = $this->option($this->optionUlid);
        $data = $this->itemData();

        $this->attempt('item.service_date', function () use ($add, $option, $data): void {
            $add->execute($this->quote, $option, $data);
            $this->reset('item');
            $this->item['net_currency'] = $this->quote->sale_currency;
        });
    }

    public function removeItem(string $ulid, RemoveItemAction $remove): void
    {
        $this->authorizeUpdate();
        $item = QuoteItem::query()
            ->whereIn('option_id', $this->quote->options()->select('id'))
            ->where('ulid', $ulid)
            ->first() ?? abort(404);

        $this->attempt('item.service_date', fn() => $remove->execute($this->quote, $item));
    }

    public function send(SendQuoteAction $send): void
    {
        $this->authorizeUpdate();
        $this->attempt('quote', function () use ($send): void {
            $version = $send->execute($this->quote, $this->actor(), CarbonImmutable::now());
            session()->flash('status', __('quotes.sent', ['version' => $version->version]));
        });
        $this->quote->refresh();
    }

    public function revise(ReviseQuoteAction $revise): void
    {
        $this->authorizeUpdate();
        $this->attempt('quote', fn(): \App\Modules\Quotes\Models\Quote => $revise->execute($this->quote));
        $this->quote->refresh();
    }

    public function accept(AcceptQuoteAction $accept): void
    {
        $this->authorizeUpdate();
        $data = $this->validate([
            'acceptance.option' => ['required', 'string'],
            'acceptance.note' => ['required', 'string', 'max:2000'],
        ], attributes: ['acceptance.option' => __('quotes.fields.accepted_option'), 'acceptance.note' => __('quotes.fields.acceptance_note')])['acceptance'];

        $this->attempt('acceptance.option', function () use ($accept, $data): void {
            $accept->execute($this->quote, $data['option'], AcceptanceChannel::Agent, $data['note'], CarbonImmutable::now());
            session()->flash('status', __('quotes.accepted'));
        });
        $this->quote->refresh();
    }

    public function cancel(CancelQuoteAction $cancel): void
    {
        $this->authorizeUpdate();
        $this->attempt('quote', fn(): \App\Modules\Quotes\Models\Quote => $cancel->execute($this->quote));
        $this->quote->refresh();
    }

    public function render(MoneyPresenter $presenter, AppSettings $settings): View
    {
        $quote = $this->quote->load(['customer:id,ulid,display_name', 'options.items']);
        $editable = $quote->status->isEditable();

        return view('quotes::livewire.quote-show', [
            'quote' => $quote,
            'activeOption' => $quote->options->firstWhere('ulid', $this->optionUlid) ?? $quote->options->first(),
            'versions' => $quote->versions()->get(['id', 'version', 'sent_at', 'valid_until']),
            'presenter' => $presenter,
            'canSeeMargin' => $this->actor()->can(Permission::MarginsView->value) || ! $settings->hideMarginsFromAgents(),
            'editable' => $editable,
            'catalogProducts' => $editable ? CatalogProduct::query()->where('is_active', true)->orderBy('name')->pluck('name', 'ulid')->all() : [],
            'suppliers' => $editable ? Supplier::query()->where('is_active', true)->orderBy('trade_name')->pluck('trade_name', 'id')->all() : [],
            'kinds' => QuoteItemKind::cases(),
            'productTypes' => ProductType::cases(),
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title($quote->number . ' · ' . $quote->title)
            ->layoutData(['heading' => $quote->number . ' · ' . $quote->title]);
    }

    private function itemData(): QuoteItemData
    {
        $isCatalog = $this->item['kind'] === QuoteItemKind::Catalog->value;
        $manual = $isCatalog ? 'exclude' : 'required';
        $data = $this->validate([
            'item.kind' => ['required', Rule::enum(QuoteItemKind::class)],
            'item.catalog_product' => [$isCatalog ? 'required' : 'exclude', Rule::exists('catalog_products', 'ulid')->where('is_active', true)],
            'item.product_type' => [$manual, Rule::enum(ProductType::class)],
            'item.description' => [$manual, 'string', 'max:255'],
            'item.net_amount' => [$manual, 'numeric', 'min:0', 'decimal:0,2'],
            'item.net_currency' => [$manual, 'string', 'size:3', 'alpha'],
            'item.supplier_id' => [$isCatalog ? 'exclude' : 'nullable', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'item.destination_country' => [$isCatalog ? 'exclude' : 'nullable', 'string', 'size:2', 'alpha'],
            'item.service_date' => ['required', 'date', 'after_or_equal:today'],
            'item.nights' => ['required', 'integer', 'min:0', 'max:' . config()->integer('travel.quotes.max_nights')],
            'item.ages' => ['required', 'string', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3})*\s*$/'],
        ], attributes: $this->prefixed())['item'];

        $ages = array_map(static fn(string $age): int => (int) trim($age), explode(self::AGES_SEPARATOR, $data['ages']));
        $this->validateAges($ages);

        return new QuoteItemData(
            kind: QuoteItemKind::from($data['kind']),
            serviceDate: CarbonImmutable::parse($data['service_date']),
            passengerAges: $ages,
            nights: (int) $data['nights'],
            catalogProductUlid: $data['catalog_product'] ?? null,
            productType: isset($data['product_type']) ? ProductType::from($data['product_type']) : null,
            description: $data['description'] ?? null,
            manualNet: isset($data['net_amount']) ? Money::of((string) $data['net_amount'], mb_strtoupper((string) $data['net_currency'])) : null,
            supplierId: ($data['supplier_id'] ?? '') === '' ? null : (int) $data['supplier_id'],
            destinationCountry: ($data['destination_country'] ?? '') === '' ? null : (string) $data['destination_country'],
        );
    }

    /** @param list<int> $ages */
    private function validateAges(array $ages): void
    {
        $label = __('quotes.item_fields.ages');
        Validator::make(['item' => ['ages' => $ages]], [
            'item.ages' => ['array', 'max:' . config()->integer('travel.quotes.max_passengers_per_item')],
            'item.ages.*' => ['integer', 'max:' . config()->integer('travel.quotes.max_passenger_age')],
        ], attributes: ['item.ages' => $label, 'item.ages.*' => $label])->validate();
    }

    /** Ejecuta un caso de uso y muestra la regla de negocio violada junto al campo indicado. */
    private function attempt(string $errorField, callable $operation): void
    {
        try {
            $operation();
        } catch (BusinessRuleException $violation) {
            // Reglas de cotizaciones, catálogo (sin temporada/tarifa) y Pricing (sin tasa de cambio).
            $this->addError($errorField, $violation->getMessage());
        }
    }

    private function option(string $ulid): QuoteOption
    {
        return QuoteOption::query()->where('quote_id', $this->quote->id)->where('ulid', $ulid)->first() ?? abort(404);
    }

    private function authorizeUpdate(): void
    {
        Gate::authorize('update', $this->quote);
    }

    /** @return array<string, string> */
    private function prefixed(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('quotes.item_fields');

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["item.{$field}" => $label])->all();
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
