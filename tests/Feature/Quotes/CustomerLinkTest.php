<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Customers\Models\Customer;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\CancelQuoteAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\ReviseQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\CustomerLinkState;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Livewire\PublicQuote;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Services\ItineraryBuilder;
use App\Modules\Quotes\Services\QuoteLinks;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Cotización enviada con un tour el día 1 y un hotel de 2 noches desde el día 1. */
function sentQuote(): Quote
{
    $agent = agent();
    $quote = app(CreateQuoteAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Cartagena', 'COP', SalesChannel::Branch);
    $tour = CatalogProduct::factory()->create(['name' => 'Tour murallas']);
    app(AddSeasonAction::class)->execute($tour, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 10000000]);
    $option = $quote->options()->firstOrFail();
    $add = app(AddItemAction::class);
    $add->execute($quote, $option, new QuoteItemData(kind: QuoteItemKind::Catalog, serviceDate: CarbonImmutable::parse('2026-11-11'), passengerAges: [40], catalogProductUlid: $tour->ulid));
    $add->execute($quote, $option, new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [40],
        nights: 2,
        productType: ProductType::Hotel,
        description: 'Hotel Caribe',
        manualNet: Money::of('400000', 'COP'),
    ));
    app(SendQuoteAction::class)->execute($quote, $agent, CarbonImmutable::now());

    return $quote->refresh();
}

function linkPath(Quote $quote): string
{
    return parse_url((string) app(QuoteLinks::class)->customerUrl($quote), PHP_URL_PATH) . '?' . parse_url((string) app(QuoteLinks::class)->customerUrl($quote), PHP_URL_QUERY);
}

it('builds the itinerary day by day in service order', function (): void {
    $days = app(ItineraryBuilder::class)->build([
        ['description' => 'Tour', 'product_type' => 'tour', 'service_date' => '2026-11-12', 'nights' => 0],
        ['description' => 'Hotel', 'product_type' => 'hotel', 'service_date' => '2026-11-10', 'nights' => 3],
        ['description' => 'Traslado', 'product_type' => 'transfer', 'service_date' => '2026-11-10', 'nights' => 0],
    ]);

    expect(array_column($days, 'day'))->toBe([1, 3])
        ->and($days[0]['entries'][0]['ends_on']?->toDateString())->toBe('2026-11-13')
        ->and(count($days[0]['entries']))->toBe(2)
        ->and(app(ItineraryBuilder::class)->build([]))->toBe([]);
});

it('opens the sent version without login only with a valid signature', function (): void {
    $quote = sentQuote();

    get(linkPath($quote))->assertOk()->assertSee('Hotel Caribe')->assertSee('Tour murallas')->assertSee(__('quotes.public.accept'))
        ->assertDontSee(__('quotes.options.margin'));
    get(route(QuoteLinks::ROUTE, ['quote' => $quote->ulid, 'version' => 1]))->assertForbidden();
    get(str_replace('/1?', '/2?', linkPath($quote)))->assertForbidden();
});

it('stops working when the quote validity ends', function (): void {
    $path = linkPath(sentQuote());
    $this->travelTo(CarbonImmutable::now()->addHours(73));

    get($path)->assertForbidden();
});

it('lets the customer accept an option of the sent version', function (): void {
    $quote = sentQuote();
    $option = $quote->options()->firstOrFail();

    Livewire::test(PublicQuote::class, ['quote' => $quote->fresh(), 'version' => 1])
        ->call('accept')
        ->assertHasErrors(['optionUlid', 'acceptedBy', 'termsAccepted'])
        ->set('optionUlid', $option->ulid)
        ->set('acceptedBy', 'Laura Pérez')
        ->set('termsAccepted', true)
        ->call('accept')
        ->assertHasNoErrors()
        ->assertSee(CustomerLinkState::Accepted->message());

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatus::Accepted)
        ->and($quote->acceptance_channel)->toBe(AcceptanceChannel::CustomerLink)
        ->and($quote->acceptance_note)->toBe(__('quotes.public.accepted_note', ['name' => 'Laura Pérez']));
});

it('explains why an old, revised, expired or cancelled link cannot be accepted', function (Closure $change, CustomerLinkState $state): void {
    $quote = sentQuote();
    $change($quote);

    Livewire::test(PublicQuote::class, ['quote' => $quote->fresh(), 'version' => 1])
        ->assertSee($state->message())
        ->assertDontSee(__('quotes.public.accept'));
})->with([
    'revised to a new draft' => [fn(Quote $quote) => app(ReviseQuoteAction::class)->execute($quote), CustomerLinkState::Superseded],
    'cancelled' => [fn(Quote $quote) => app(CancelQuoteAction::class)->execute($quote), CustomerLinkState::Cancelled],
    'past validity' => [fn() => test()->travelTo(CarbonImmutable::now()->addHours(80)), CustomerLinkState::Expired],
]);

it('refuses to accept a superseded version even if the page was already open', function (): void {
    $quote = sentQuote();
    $page = Livewire::test(PublicQuote::class, ['quote' => $quote->fresh(), 'version' => 1])
        ->set('optionUlid', $quote->options()->firstOrFail()->ulid)
        ->set('acceptedBy', 'Laura')
        ->set('termsAccepted', true);
    app(ReviseQuoteAction::class)->execute($quote);
    app(SendQuoteAction::class)->execute($quote->refresh(), agent(), CarbonImmutable::now());

    $page->call('accept')->assertHasErrors('optionUlid');
    expect($quote->fresh()?->status)->toBe(QuoteStatus::Sent);
});

it('reports business errors such as an expired quote on acceptance', function (): void {
    $quote = sentQuote();
    $page = Livewire::test(PublicQuote::class, ['quote' => $quote->fresh(), 'version' => 1])
        ->set('optionUlid', $quote->options()->firstOrFail()->ulid)
        ->set('acceptedBy', 'Laura')
        ->set('termsAccepted', true);
    $this->travelTo(CarbonImmutable::now()->addHours(80));

    $page->call('accept')->assertHasErrors('optionUlid');
    expect($quote->fresh()?->status)->toBe(QuoteStatus::Expired);
});

it('answers 404 for unknown versions', function (): void {
    $quote = sentQuote();

    get(Illuminate\Support\Facades\URL::temporarySignedRoute(QuoteLinks::ROUTE, CarbonImmutable::now()->addHour(), ['quote' => $quote->ulid, 'version' => 7]))->assertNotFound();
});

it('limits link requests per IP', function (): void {
    config(['travel.quotes.customer_link_requests_per_minute' => 2]);
    $path = linkPath(sentQuote());

    get($path)->assertOk();
    get($path)->assertOk();
    get($path)->assertTooManyRequests();
});

it('shows the agent the customer link and the itinerary only when sent', function (): void {
    $quote = sentQuote();
    actingAs(App\Modules\Identity\Models\User::query()->findOrFail($quote->owner_id));

    Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->assertSee(__('quotes.public.copy_link'))
        ->assertSee(__('quotes.itinerary.title'))
        ->assertSee(__('quotes.itinerary.until', ['nights' => 2, 'date' => CarbonImmutable::parse('2026-11-12')->locale(app()->getLocale())->isoFormat('ll')]))
        ->call('revise')
        ->assertDontSee(__('quotes.public.copy_link'));

    expect(app(QuoteLinks::class)->customerUrl(Quote::factory()->create()))->toBeNull();
});
