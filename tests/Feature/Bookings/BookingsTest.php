<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\CreateBookingFromQuoteAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Livewire\BookingsIndex;
use App\Modules\Bookings\Livewire\ConvertQuote;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Exceptions\QuoteRuleViolation;
use App\Modules\Quotes\Models\Quote;
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

/** Cotización aceptada: tour propio (2 pax) con salida de 3 cupos y hotel manual. */
function acceptedQuote(?User $agent = null): Quote
{
    $agent ??= agent();
    $quote = app(CreateQuoteAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Cartagena', 'COP', SalesChannel::Branch);
    $tour = CatalogProduct::factory()->create(['name' => 'Tour murallas']);
    app(AddSeasonAction::class)->execute($tour, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 10000000]);
    app(AddDepartureAction::class)->execute($tour, CarbonImmutable::parse('2026-11-11'), '09:00', 3);
    $option = $quote->options()->firstOrFail();
    $add = app(AddItemAction::class);
    $add->execute($quote, $option, new QuoteItemData(kind: QuoteItemKind::Catalog, serviceDate: CarbonImmutable::parse('2026-11-11'), passengerAges: [40, 38], catalogProductUlid: $tour->ulid));
    $add->execute($quote, $option, new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [40, 38],
        nights: 2,
        productType: ProductType::Hotel,
        description: 'Hotel Caribe',
        manualNet: Money::of('400000', 'COP'),
    ));
    app(SendQuoteAction::class)->execute($quote, $agent, CarbonImmutable::now());
    app(AcceptQuoteAction::class)->execute($quote, $option->ulid, AcceptanceChannel::Agent, 'Por teléfono', CarbonImmutable::now());

    return $quote->refresh();
}

function bookingOf(Quote $quote): Booking
{
    return app(CreateBookingFromQuoteAction::class)->execute($quote->ulid, CarbonImmutable::now());
}

function itemNamed(Booking $booking, string $description): BookingItem
{
    return $booking->items()->where('description', $description)->sole();
}

it('derives the booking status from its items', function (array $items, BookingStatus $expected): void {
    expect(BookingStatus::derive($items))->toBe($expected);
})->with([
    'nothing confirmed yet' => [[BookingItemStatus::Pending, BookingItemStatus::Confirmed], BookingStatus::InProgress],
    'waiting for supplier' => [[BookingItemStatus::OnHold], BookingStatus::InProgress],
    'all confirmed' => [[BookingItemStatus::Confirmed, BookingItemStatus::Confirmed], BookingStatus::Confirmed],
    'confirmed plus discarded' => [[BookingItemStatus::Confirmed, BookingItemStatus::Cancelled], BookingStatus::Confirmed],
    'rejected needs attention' => [[BookingItemStatus::Confirmed, BookingItemStatus::Rejected], BookingStatus::NeedsAttention],
    'everything cancelled' => [[BookingItemStatus::Cancelled, BookingItemStatus::Cancelled], BookingStatus::Cancelled],
]);

it('converts the accepted option with frozen prices, owner and branch of the quote', function (): void {
    $quote = acceptedQuote();

    $booking = bookingOf($quote);

    expect($booking->number)->toStartWith(config()->string('travel.bookings.number_prefix'))
        ->and($booking->status)->toBe(BookingStatus::InProgress)
        ->and($booking->owner_id)->toBe($quote->owner_id)
        ->and($booking->branch_id)->toBe($quote->branch_id)
        ->and($booking->quote_number)->toBe($quote->number)
        ->and($booking->items()->count())->toBe(2)
        ->and(itemNamed($booking, 'Tour murallas')->isOwnProduct())->toBeTrue()
        ->and(itemNamed($booking, 'Hotel Caribe')->sale_amount_minor)->toBe($quote->options()->firstOrFail()->items()->where('description', 'Hotel Caribe')->value('sale_amount_minor'));
});

it('converts each quote only once and only when accepted', function (): void {
    $quote = acceptedQuote();
    $booking = bookingOf($quote);

    expect(fn(): \App\Modules\Bookings\Models\Booking => bookingOf($quote))->toThrow(BookingRuleViolation::class, __('bookings.errors.already_converted', ['number' => $booking->number]))
        ->and(fn(): \App\Modules\Bookings\Models\Booking => bookingOf(Quote::factory()->create()))->toThrow(QuoteRuleViolation::class);
});

it('confirms supplier services with their confirmation code', function (): void {
    $booking = bookingOf(acceptedQuote());
    $hotel = itemNamed($booking, 'Hotel Caribe');

    app(ConfirmItemAction::class)->execute($hotel, 'HCR-889', null, CarbonImmutable::now());

    $hotel->refresh();
    expect($hotel->status)->toBe(BookingItemStatus::Confirmed)
        ->and($hotel->supplier_confirmation)->toBe('HCR-889')
        ->and($booking->fresh()?->status)->toBe(BookingStatus::InProgress);
});

it('holds own product seats on confirmation and confirms the booking when everything is confirmed', function (): void {
    $booking = bookingOf(acceptedQuote());
    $departure = CatalogDeparture::query()->sole();
    $confirm = app(ConfirmItemAction::class);

    $confirm->execute(itemNamed($booking, 'Hotel Caribe'), 'HCR-1', null, CarbonImmutable::now());
    $confirm->execute(itemNamed($booking, 'Tour murallas'), 'PROPIO', $departure->ulid, CarbonImmutable::now());

    expect($departure->fresh()?->reserved_seats)->toBe(2)
        ->and(itemNamed($booking, 'Tour murallas')->seat_hold_ulid)->not->toBeNull()
        ->and($booking->fresh()?->status)->toBe(BookingStatus::Confirmed);
});

it('requires a valid departure with enough seats for own products', function (): void {
    $booking = bookingOf(acceptedQuote());
    $tour = itemNamed($booking, 'Tour murallas');
    $other = app(AddDepartureAction::class)->execute(CatalogProduct::factory()->create(), CarbonImmutable::parse('2026-11-11'), '09:00', 9);
    $departure = CatalogDeparture::query()->where('capacity', 3)->sole();
    $departure->reserved_seats = 2;
    $departure->save();
    $confirm = app(ConfirmItemAction::class);

    expect(fn() => $confirm->execute($tour, 'X', null, CarbonImmutable::now()))->toThrow(BookingRuleViolation::class, __('bookings.errors.departure_required'))
        ->and(fn() => $confirm->execute($tour, 'X', $other->ulid, CarbonImmutable::now()))->toThrow(BookingRuleViolation::class, __('bookings.errors.departure_not_available'))
        ->and(fn() => $confirm->execute($tour, 'X', $departure->ulid, CarbonImmutable::now()))->toThrow(CatalogRuleViolation::class);
    expect($tour->fresh()?->status)->toBe(BookingItemStatus::Pending);
});

it('releases held seats when an own product service is cancelled', function (): void {
    $booking = bookingOf(acceptedQuote());
    $tour = itemNamed($booking, 'Tour murallas');
    $departure = CatalogDeparture::query()->sole();
    app(ConfirmItemAction::class)->execute($tour, 'PROPIO', $departure->ulid, CarbonImmutable::now());

    app(ChangeItemStatusAction::class)->execute($tour->fresh() ?? $tour, BookingItemStatus::Cancelled, 'El cliente desiste', CarbonImmutable::now());

    expect($departure->fresh()?->reserved_seats)->toBe(0)
        ->and($tour->fresh()?->status_note)->toBe('El cliente desiste');
});

it('flags rejected services until they are discarded', function (): void {
    $booking = bookingOf(acceptedQuote());
    $change = app(ChangeItemStatusAction::class);
    app(ConfirmItemAction::class)->execute(itemNamed($booking, 'Tour murallas'), 'P', CatalogDeparture::query()->value('ulid'), CarbonImmutable::now());
    $hotel = itemNamed($booking, 'Hotel Caribe');

    $change->execute($hotel, BookingItemStatus::OnHold, 'Esperando respuesta', CarbonImmutable::now());
    $change->execute($hotel->fresh() ?? $hotel, BookingItemStatus::Rejected, 'Sin habitaciones', CarbonImmutable::now());
    expect($booking->fresh()?->status)->toBe(BookingStatus::NeedsAttention);

    $change->execute($hotel->fresh() ?? $hotel, BookingItemStatus::Cancelled, 'Se cotizará otro hotel', CarbonImmutable::now());
    expect($booking->fresh()?->status)->toBe(BookingStatus::Confirmed);
});

it('enforces the item state machine', function (BookingItemStatus $next): void {
    $booking = bookingOf(acceptedQuote());
    $hotel = itemNamed($booking, 'Hotel Caribe');
    app(ChangeItemStatusAction::class)->execute($hotel, BookingItemStatus::Cancelled, 'x', CarbonImmutable::now());

    expect(fn() => app(ChangeItemStatusAction::class)->execute($hotel->fresh() ?? $hotel, $next, 'y', CarbonImmutable::now()))->toThrow(BookingRuleViolation::class);
})->with([BookingItemStatus::OnHold, BookingItemStatus::Rejected, BookingItemStatus::Confirmed, BookingItemStatus::Pending]);

it('labels booking enums', function (): void {
    foreach ([...BookingStatus::cases(), ...BookingItemStatus::cases()] as $case) {
        expect($case->label())->not->toStartWith('bookings.');
    }
});

it('converts from the screen only quotes in scope', function (): void {
    $agent = agent();
    $quote = acceptedQuote($agent);

    actingAs(agent())->get(route('bookings.from-quote', $quote->ulid))->assertNotFound();
    actingAs($agent)->get(route('bookings.from-quote', Quote::factory()->ownedBy($agent)->create()->ulid))->assertNotFound();
    actingAs($agent)->get(route('quotes.show', $quote))->assertSee(__('bookings.convert.from_quote_link'));

    Livewire::test(ConvertQuote::class, ['quote' => $quote->ulid])->assertSee('Hotel Caribe')->call('convert')->assertRedirect();
    $booking = Booking::query()->sole();

    get(route('bookings.from-quote', $quote->ulid))->assertOk()->assertSee(route('bookings.show', $booking))->assertDontSee('wire:click="convert"', false);
});

it('reports conversion conflicts on the screen', function (): void {
    $agent = agent();
    $quote = acceptedQuote($agent);
    actingAs($agent);
    $page = Livewire::test(ConvertQuote::class, ['quote' => $quote->ulid]);
    bookingOf($quote);

    $page->call('convert')->assertHasErrors('quote');
});

it('lists and shows bookings within the scope', function (): void {
    $agent = agent();
    $booking = bookingOf(acceptedQuote($agent));

    get(route('bookings.index'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('bookings.show', $booking))->assertNotFound();
    actingAs($agent)->get(route('bookings.show', $booking))->assertOk()->assertSee($booking->number)->assertDontSee(__('bookings.margin'));
    actingAs(userWithRole(Role::AgencyOwner))->get(route('bookings.show', $booking))->assertSee(__('bookings.margin'));

    actingAs($agent);
    Livewire::test(BookingsIndex::class)->assertSee($booking->number);
    Livewire::test(BookingsIndex::class)->set('status', BookingStatus::Confirmed->value)->assertSee(__('bookings.empty_title'));
    Livewire::test(BookingsIndex::class)->set('status', 'x')->set('search', $booking->quote_number)->assertSee($booking->number);
    actingAs(agent());
    Livewire::test(BookingsIndex::class)->assertDontSee($booking->number);
});

it('manages services from the booking screen', function (): void {
    $agent = agent();
    $booking = bookingOf(acceptedQuote($agent));
    $tour = itemNamed($booking, 'Tour murallas');
    $hotel = itemNamed($booking, 'Hotel Caribe');
    actingAs($agent);

    Livewire::test(BookingShow::class, ['booking' => $booking])
        ->call('manage', $tour->ulid)
        ->set('action.status', BookingItemStatus::Confirmed->value)
        ->assertSee(__('bookings.actions.departure_option', ['time' => '09:00', 'available' => 3]))
        ->call('apply')
        ->assertHasErrors(['action.confirmation', 'action.departure'])
        ->set('action.confirmation', 'PROPIO-1')
        ->set('action.departure', CatalogDeparture::query()->value('ulid'))
        ->call('apply')
        ->assertHasNoErrors()
        ->assertSee(__('bookings.items.departure_held'))
        ->call('manage', $hotel->ulid)
        ->set('action.status', BookingItemStatus::Rejected->value)
        ->call('apply')
        ->assertHasErrors('action.note')
        ->set('action.note', 'Sin cupo')
        ->call('apply')
        ->assertHasNoErrors()
        ->assertSee(BookingStatus::NeedsAttention->label())
        ->call('manage', $hotel->ulid)
        ->set('action.status', BookingItemStatus::Confirmed->value)
        ->set('action.confirmation', 'X')
        ->call('apply')
        ->assertHasErrors('action.status');
});

it('shows a hint when an own product has no departures that day', function (): void {
    $agent = agent();
    $booking = bookingOf(acceptedQuote($agent));
    CatalogDeparture::query()->delete();
    actingAs($agent);

    Livewire::test(BookingShow::class, ['booking' => $booking])
        ->call('manage', itemNamed($booking, 'Tour murallas')->ulid)
        ->set('action.status', BookingItemStatus::Confirmed->value)
        ->assertSee(__('bookings.actions.no_departures'));
});

it('does not manage services of other bookings', function (): void {
    $agent = agent();
    $mine = bookingOf(acceptedQuote($agent));
    $other = bookingOf(acceptedQuote($agent));
    actingAs($agent);

    Livewire::test(BookingShow::class, ['booking' => $mine])->call('manage', $other->items()->value('ulid'))->assertNotFound();
});
