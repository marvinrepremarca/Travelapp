<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\AddOptionAction;
use App\Modules\Quotes\Actions\CancelQuoteAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\ExpireQuotesAction;
use App\Modules\Quotes\Actions\RemoveItemAction;
use App\Modules\Quotes\Actions\RemoveOptionAction;
use App\Modules\Quotes\Actions\ReviseQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Events\QuoteAccepted;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    $this->seed(TaxReferenceSeeder::class);
    MarkupRule::factory()->percentage(1000)->create(['name' => 'General 10']);
});

function newQuote(): Quote
{
    return app(CreateQuoteAction::class)->execute(agent(), Customer::factory()->create(), 'Cartagena en familia', 'COP', SalesChannel::Branch);
}

function catalogTour(): CatalogProduct
{
    $tour = CatalogProduct::factory()->create(['name' => 'City tour']);
    app(AddSeasonAction::class)->execute($tour, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 10000000, 'child' => 5000000]);

    return $tour;
}

function addCatalogItem(Quote $quote, ?CatalogProduct $product = null, string $ages = '35,33,8'): QuoteItem
{
    return app(AddItemAction::class)->execute($quote, $quote->options()->firstOrFail(), new QuoteItemData(
        kind: QuoteItemKind::Catalog,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: array_map(intval(...), explode(',', $ages)),
        catalogProductUlid: ($product ?? catalogTour())->ulid,
    ));
}

it('creates a numbered draft owned by the agent in their branch with option A', function (): void {
    $quote = newQuote();

    expect($quote->status)->toBe(QuoteStatus::Draft)
        ->and($quote->number)->toBe(config('travel.quotes.number_prefix') . str_pad((string) $quote->id, config()->integer('travel.quotes.number_digits'), '0', STR_PAD_LEFT))
        ->and($quote->options()->pluck('label')->all())->toBe(['A'])
        ->and($quote->branch_id)->not->toBeNull();
});

it('prices catalog items on the server by age and pricing rules', function (): void {
    $item = addCatalogItem(newQuote());

    // Neto: 2 adultos × 100.000 + 1 niño × 50.000; markup 10 %; IVA 19 % sobre el markup.
    expect((string) $item->netAmount()->getAmount())->toBe('250000.00')
        ->and((string) $item->marginAmount()->getAmount())->toBe('25000.00')
        ->and((string) $item->saleAmount()->getAmount())->toBe('279750.00')
        ->and($item->description)->toBe('City tour')
        ->and($item->product_type)->toBe(ProductType::Tour)
        ->and($item->price_breakdown['components'])->toHaveCount(3);
});

it('prices manual supplier items converting the net to the sale currency', function (): void {
    app(RecordExchangeRateAction::class)->execute('USD', 'COP', BigDecimal::of('4000'), ExchangeRateSource::Manual, CarbonImmutable::parse('2026-11-01'));
    $quote = newQuote();

    $item = app(AddItemAction::class)->execute($quote, $quote->options()->firstOrFail(), new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [35, 33],
        nights: 3,
        productType: ProductType::Hotel,
        description: 'Hotel Caribe 3 noches',
        manualNet: Money::of('300', 'USD'),
        destinationCountry: 'co',
    ));

    expect((string) $item->netAmount()->getAmount())->toBe('300.00')
        ->and($item->net_currency)->toBe('USD')
        ->and((string) $item->saleAmount()->getAmount())->toBe('1342800.00')
        ->and($item->destination_country)->toBe('CO')
        ->and($item->price_breakdown['exchange_rate']['rate_date'])->toBe('2026-11-01');
});

it('requires the manual net', function (): void {
    $quote = newQuote();

    app(AddItemAction::class)->execute($quote, $quote->options()->firstOrFail(), new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [35],
        productType: ProductType::Hotel,
        description: 'Hotel',
    ));
})->throws(QuoteRuleViolation::class);

it('adds options up to the configured maximum and keeps at least one', function (): void {
    config(['travel.quotes.max_options' => 2]);
    $quote = newQuote();
    $add = app(AddOptionAction::class);

    $optionB = $add->execute($quote, 'Plan premium');

    expect($optionB->label)->toBe('B')
        ->and(fn() => $add->execute($quote, 'Plan C'))->toThrow(QuoteRuleViolation::class, __('quotes.errors.too_many_options', ['max' => 2]));

    $remove = app(RemoveOptionAction::class);
    $remove->execute($quote, $quote->options()->where('label', 'A')->firstOrFail());
    expect(fn() => $remove->execute($quote, $optionB))->toThrow(QuoteRuleViolation::class, __('quotes.errors.last_option'));
    expect($add->execute($quote, 'Otra vez A')->label)->toBe('A');
});

it('sends an immutable version with the configured validity', function (): void {
    Event::fake([QuoteSent::class]);
    $quote = newQuote();
    addCatalogItem($quote);

    $version = app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now());

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Sent)
        ->and($quote->current_version)->toBe(1)
        ->and($quote->valid_until?->toDateTimeString())->toBe('2026-10-04 15:00:00')
        ->and($version->snapshot['options'][0]['items'][0]['sale_amount_minor'])->toBe(27975000)
        ->and($version->snapshot['options'][0]['sale_total_minor'])->toBe(27975000);
    Event::assertDispatched(QuoteSent::class, static fn(QuoteSent $event): bool => $event->quoteUlid === $quote->ulid && $event->version === 1);

    expect(fn() => $version->update(['version' => 9]))->toThrow(LogicException::class)
        ->and(fn() => $version->delete())->toThrow(LogicException::class);
});

it('refuses to send options without items', function (): void {
    $quote = newQuote();
    addCatalogItem($quote);
    app(AddOptionAction::class)->execute($quote, 'Vacía');

    expect(fn() => app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now()))
        ->toThrow(QuoteRuleViolation::class, __('quotes.errors.empty_option', ['label' => 'B']));
    expect($quote->fresh()?->status)->toBe(QuoteStatus::Draft);
});

it('locks a sent quote until a new version is drafted with recalculated prices', function (): void {
    $quote = newQuote();
    $item = addCatalogItem($quote);
    app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now());
    $quote->refresh();

    expect(fn() => app(RemoveItemAction::class)->execute($quote, $item))->toThrow(QuoteRuleViolation::class);

    MarkupRule::query()->update(['rate_basis_points' => 2000]);
    app(ReviseQuoteAction::class)->execute($quote);
    app(SendQuoteAction::class)->execute($quote->refresh(), agent(), CarbonImmutable::now());

    $versions = QuoteVersion::query()->where('quote_id', $quote->id)->orderBy('version')->get();
    expect($versions)->toHaveCount(2)
        ->and($versions[0]->snapshot['options'][0]['sale_total_minor'])->toBe(27975000)
        ->and($versions[1]->snapshot['options'][0]['sale_total_minor'])->toBe(30950000);
});

it('accepts an option of the sent version before it expires', function (): void {
    Event::fake([QuoteAccepted::class]);
    $quote = newQuote();
    addCatalogItem($quote);
    app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now());
    $option = $quote->options()->firstOrFail();

    $accepted = app(AcceptQuoteAction::class)->execute($quote, $option->ulid, AcceptanceChannel::Agent, 'Confirmó por teléfono', CarbonImmutable::now()->addHours(71));

    expect($accepted->status)->toBe(QuoteStatus::Accepted)
        ->and($accepted->accepted_option_id)->toBe($option->id)
        ->and($accepted->accepted_version)->toBe(1)
        ->and($accepted->acceptance_channel)->toBe(AcceptanceChannel::Agent);
    Event::assertDispatched(QuoteAccepted::class);
    expect(fn() => app(CancelQuoteAction::class)->execute($accepted))->toThrow(QuoteRuleViolation::class);
});

it('rejects acceptance after the validity and marks the quote expired', function (): void {
    $quote = newQuote();
    addCatalogItem($quote);
    app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now());

    expect(fn() => app(AcceptQuoteAction::class)->execute($quote, $quote->options()->firstOrFail()->ulid, AcceptanceChannel::Agent, 'Tarde', CarbonImmutable::now()->addHours(72)))
        ->toThrow(QuoteRuleViolation::class, __('quotes.errors.expired'));
    expect($quote->fresh()?->status)->toBe(QuoteStatus::Expired);
});

it('only accepts options that were sent', function (): void {
    $quote = newQuote();
    addCatalogItem($quote);
    app(SendQuoteAction::class)->execute($quote, agent(), CarbonImmutable::now());

    expect(fn() => app(AcceptQuoteAction::class)->execute($quote, 'otra-opcion', AcceptanceChannel::Agent, null, CarbonImmutable::now()))
        ->toThrow(QuoteRuleViolation::class, __('quotes.errors.option_not_in_version'));
    expect(fn() => app(AcceptQuoteAction::class)->execute(newQuote(), 'x', AcceptanceChannel::Agent, null, CarbonImmutable::now()))
        ->toThrow(QuoteRuleViolation::class);
});

it('expires sent quotes past their validity from the scheduler', function (): void {
    $sent = newQuote();
    addCatalogItem($sent);
    app(SendQuoteAction::class)->execute($sent, agent(), CarbonImmutable::now());
    $draft = newQuote();
    $this->travelTo(CarbonImmutable::now()->addHours(73));

    $this->artisan('quotes:expire')->expectsOutput(__('quotes.expired_count', ['count' => 1]))->assertSuccessful();

    expect($sent->fresh()?->status)->toBe(QuoteStatus::Expired)
        ->and($draft->fresh()?->status)->toBe(QuoteStatus::Draft)
        ->and(app(ExpireQuotesAction::class)->execute(CarbonImmutable::now()))->toBe(0);
});

it('cancels drafts, sent and expired quotes but nothing twice', function (): void {
    $cancelled = app(CancelQuoteAction::class)->execute(newQuote());

    expect($cancelled->status)->toBe(QuoteStatus::Cancelled)
        ->and(fn() => app(CancelQuoteAction::class)->execute($cancelled))->toThrow(QuoteRuleViolation::class)
        ->and(fn() => app(ReviseQuoteAction::class)->execute($cancelled))->toThrow(QuoteRuleViolation::class);
});

it('labels quote enums and only lets drafts be edited', function (): void {
    foreach ([...QuoteStatus::cases(), ...QuoteItemKind::cases(), ...AcceptanceChannel::cases()] as $case) {
        expect($case->label())->not->toStartWith('quotes.');
    }

    expect(array_filter(QuoteStatus::cases(), static fn(QuoteStatus $status): bool => $status->isEditable()))->toBe([QuoteStatus::Draft]);
});

it('is scoped to the agent, their branch or everyone', function (): void {
    $quote = newQuote();
    $owner = App\Modules\Identity\Models\User::query()->findOrFail($quote->owner_id);

    expect($quote->isVisibleTo($owner))->toBeTrue()
        ->and($quote->isVisibleTo(agent()))->toBeFalse()
        ->and($quote->isVisibleTo(userWithRole(Role::AgencyOwner)))->toBeTrue();
});
