<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\AssignPassengersAction;
use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\CreateBookingFromQuoteAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Identity\Models\User;
use App\Modules\Integrations\Adapters\Fake\FakeFlights;
use App\Modules\Integrations\Adapters\Fake\FakeHotels;
use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteItem;
use App\Modules\Search\Contracts\SupplierGateway;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\ProviderBookingRequest;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use App\Modules\Search\Livewire\FlightSearch;
use App\Modules\Search\Livewire\HotelSearch;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    app(RecordExchangeRateAction::class)->execute('USD', 'COP', BigDecimal::of('4000'), ExchangeRateSource::Manual, CarbonImmutable::parse('2026-10-01'));
});

/** El asesor busca vuelos BOG→$destination y agrega la primera oferta a su borrador. */
function quoteWithFlight(User $agent, string $destination = 'CTG'): Quote
{
    $quote = app(CreateQuoteAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Viaje', 'COP', SalesChannel::Branch);
    actingAs($agent);
    $offer = app(FakeFlights::class)->search(new FlightSearchCriteria('BOG', $destination, CarbonImmutable::parse('2026-11-10'), [40]))[0];

    Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'BOG')
        ->set('criteria.destination', $destination)
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '40')
        ->call('search')
        ->set('targetQuote', $quote->ulid)
        ->call('addToQuote', FakeFlights::KEY . $offer->offerId)
        ->assertHasNoErrors()
        ->assertSee(__('search.added_to_quote'));

    return $quote->refresh();
}

function bookedFlight(User $agent, string $destination = 'CTG'): Booking
{
    $quote = quoteWithFlight($agent, $destination);
    app(SendQuoteAction::class)->execute($quote, $agent, CarbonImmutable::now());
    app(AcceptQuoteAction::class)->execute($quote, $quote->options()->firstOrFail()->ulid, AcceptanceChannel::Agent, 'ok', CarbonImmutable::now());

    return app(CreateBookingFromQuoteAction::class)->execute($quote->ulid, CarbonImmutable::now());
}

function withPassenger(Booking $booking): void
{
    $traveler = Traveler::factory()->create(['customer_id' => $booking->customer_id, 'first_name' => 'Laura', 'last_name' => 'Pérez', 'birth_date' => '1986-05-01']);
    app(AssignPassengersAction::class)->execute($booking->items()->sole(), [$traveler->ulid]);
}

it('adds a searched offer to a draft quote keeping the provider reference', function (): void {
    $quote = quoteWithFlight(agent());
    $item = QuoteItem::query()->sole();

    expect($item->provider_key)->toBe(FakeFlights::KEY)
        ->and($item->provider_offer_id)->toStartWith('fake-')
        ->and($item->product_type)->toBe(ProductType::Flight)
        ->and($item->description)->toContain('BOG')
        ->and($item->net_currency)->toBe('USD')
        ->and($item->sale_amount_minor)->toBeGreaterThan(0)
        ->and($quote->options()->firstOrFail()->items()->count())->toBe(1);
});

it('adds hotels with nights and destination to the quote', function (): void {
    $agent = agent();
    $quote = app(CreateQuoteAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Hotel', 'COP', SalesChannel::Branch);
    actingAs($agent);
    $screen = Livewire::test(HotelSearch::class)
        ->set('criteria.city', 'Cartagena')
        ->set('criteria.country', 'CO')
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2026-11-12')
        ->set('criteria.ages', '40, 38')
        ->call('search')
        ->assertSee($quote->number);
    $offerId = app(FakeHotels::class)->search(new App\Modules\Search\Data\HotelSearchCriteria('Cartagena', 'CO', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-12'), [40, 38]))[1]->offerId;

    $screen->set('targetQuote', $quote->ulid)->call('addToQuote', FakeHotels::KEY . $offerId)->assertHasNoErrors();

    $item = QuoteItem::query()->sole();
    expect($item->nights)->toBe(2)->and($item->destination_country)->toBe('CO')->and($item->provider_key)->toBe(FakeHotels::KEY);
});

it('refuses to add offers without a draft in scope or for unknown offers', function (): void {
    $agent = agent();
    $foreign = app(CreateQuoteAction::class)->execute(agent(), Customer::factory()->create(), 'Ajena', 'COP', SalesChannel::Branch);
    actingAs($agent);
    $screen = Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'BOG')->set('criteria.destination', 'CTG')->set('criteria.departure_date', '2026-11-10')->set('criteria.ages', '40')
        ->call('search')
        ->assertSee(__('search.no_drafts'));

    $screen->call('addToQuote', 'fake-no-existe')->assertHasErrors('targetQuote');
    $offer = app(FakeFlights::class)->search(new FlightSearchCriteria('BOG', 'CTG', CarbonImmutable::parse('2026-11-10'), [40]))[0];
    $screen->set('targetQuote', $foreign->ulid)->call('addToQuote', FakeFlights::KEY . $offer->offerId)->assertHasErrors('targetQuote');
    expect(QuoteItem::query()->count())->toBe(0);
});

it('reserves with the provider after re-pricing and records its reference', function (): void {
    $booking = bookedFlight(agent());
    withPassenger($booking);
    $item = $booking->items()->sole();

    app(ConfirmItemAction::class)->execute($item, null, null, CarbonImmutable::now());

    $item->refresh();
    expect($item->status)->toBe(BookingItemStatus::Confirmed)
        ->and($item->provider_booking_reference)->toStartWith('FAKE-')
        ->and($item->supplier_confirmation)->toBe($item->provider_booking_reference);
});

it('never books twice for the same service', function (): void {
    $request = new ProviderBookingRequest('fake-x', 'booking-item:abc', ['Laura Pérez'], 'EXP-1');
    $gateway = app(SupplierGateway::class);

    expect($gateway->book(ProductType::Flight, FakeFlights::KEY, $request))->toBe($gateway->book(ProductType::Flight, FakeFlights::KEY, $request));
});

it('stops the confirmation when the provider changed the price', function (): void {
    $booking = bookedFlight(agent(), FakeFlights::PRICE_CHANGE_DESTINATION);
    withPassenger($booking);
    $item = $booking->items()->sole();

    expect(fn() => app(ConfirmItemAction::class)->execute($item, null, null, CarbonImmutable::now()))
        ->toThrow(BookingRuleViolation::class);
    expect($item->fresh()?->status)->toBe(BookingItemStatus::Pending)
        ->and($item->fresh()?->provider_booking_reference)->toBeNull();
});

it('stops the confirmation when the offer expired or there are no passengers', function (): void {
    $booking = bookedFlight(agent());
    $item = $booking->items()->sole();

    expect(fn() => app(ConfirmItemAction::class)->execute($item, null, null, CarbonImmutable::now()))
        ->toThrow(BookingRuleViolation::class, __('bookings.errors.passengers_required_for_provider'));

    withPassenger($booking);
    Cache::flush();
    expect(fn() => app(ConfirmItemAction::class)->execute($item->fresh() ?? $item, null, null, CarbonImmutable::now()))
        ->toThrow(BookingRuleViolation::class, __('bookings.errors.offer_no_longer_available'));
});

it('requires a confirmation code for manual services', function (): void {
    $booking = familyBooking(agent());

    app(ConfirmItemAction::class)->execute(hotelOf($booking), null, null, CarbonImmutable::now());
})->throws(BookingRuleViolation::class);

it('cancels with the provider before cancelling the service', function (): void {
    $booking = bookedFlight(agent());
    withPassenger($booking);
    $item = $booking->items()->sole();
    app(ConfirmItemAction::class)->execute($item, null, null, CarbonImmutable::now());

    app(ChangeItemStatusAction::class)->execute($item->fresh() ?? $item, BookingItemStatus::Cancelled, 'El cliente desiste', CarbonImmutable::now());

    expect($item->fresh()?->status)->toBe(BookingItemStatus::Cancelled);
});

it('reports unknown providers and products without integration as unavailable', function (ProductType $type, string $key): void {
    app(SupplierGateway::class)->reprice($type, $key, 'x');
})->throws(ProviderUnavailable::class)->with([
    'unknown flight provider' => [ProductType::Flight, 'amadeus'],
    'product without providers' => [ProductType::Tour, FakeFlights::KEY],
]);

it('re-prices fake offers only while they are remembered', function (): void {
    $offer = app(FakeHotels::class)->search(new App\Modules\Search\Data\HotelSearchCriteria(FakeHotels::PRICE_CHANGE_CITY, 'CO', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-11'), [30]))[0];
    $gateway = app(SupplierGateway::class);

    expect($gateway->reprice(ProductType::Hotel, FakeHotels::KEY, $offer->offerId)?->isGreaterThan($offer->totalNet))->toBeTrue()
        ->and($gateway->reprice(ProductType::Hotel, FakeHotels::KEY, 'desconocida'))->toBeNull();
    $gateway->cancel(ProductType::Hotel, FakeHotels::KEY, 'FAKE-1');
});
