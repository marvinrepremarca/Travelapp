<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\AddDirectItemAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\CreateDirectBookingAction;
use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Data\DirectItemData;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Livewire\DirectBookingForm;
use App\Modules\Bookings\Livewire\DirectItemForm;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Models\RevenueEntry;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\RecordCustomerPaymentAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Models\Payment;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function directBooking(User $agent): Booking
{
    return app(CreateDirectBookingAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Viaje a Santa Marta', 'COP');
}

function directHotel(Money $net): DirectItemData
{
    return new DirectItemData('Hotel Rodadero', ProductType::Hotel, null, 'CO', CarbonImmutable::parse('2026-11-10'), 2, [35, 33], $net, SalesChannel::Branch);
}

it('creates a booking without a quote and prices its services on the server', function (): void {
    $agent = agent();
    $booking = directBooking($agent);

    $item = app(AddDirectItemAction::class)->execute($booking, directHotel(Money::of('1000000', 'COP')), CarbonImmutable::now());

    expect($booking->quote_ulid)->toBeNull()
        ->and($booking->number)->not->toBeNull()
        ->and($booking->owner_id)->toBe($agent->id)
        ->and($item->status)->toBe(BookingItemStatus::Pending)
        ->and($item->net_amount_minor)->toBe(100_000_000)
        ->and($item->sale_amount_minor)->toBeGreaterThan($item->net_amount_minor)
        ->and($item->margin_amount_minor)->toBeGreaterThan(0)
        ->and($item->price_breakdown['currency'])->toBe('COP');
});

it('confirms direct services like any other so accounting recognizes them', function (): void {
    $booking = directBooking(agent());
    $item = app(AddDirectItemAction::class)->execute($booking, directHotel(Money::of('500000', 'COP')), CarbonImmutable::now());

    app(ConfirmItemAction::class)->execute($item, 'HRD-1', null, CarbonImmutable::now());

    expect(RevenueEntry::query()->sole()->amount_minor)->toBe($item->sale_amount_minor);
});

it('refuses to add services to bookings that come from a quote or with invalid data', function (): void {
    $quoted = familyBooking(agent());
    $direct = directBooking(agent());
    $add = app(AddDirectItemAction::class);

    expect(fn() => $add->execute($quoted, directHotel(Money::of('1', 'COP')), CarbonImmutable::now()))->toThrow(BookingRuleViolation::class, __('bookings.errors.items_come_from_quote'))
        ->and(fn() => $add->execute($direct, directHotel(Money::zero('COP')), CarbonImmutable::now()))->toThrow(BookingRuleViolation::class, __('bookings.errors.direct_item_invalid'));
});

it('creates a direct booking and adds a service from the screens with quoting off', function (): void {
    disableCapabilities(Capability::Quoting);
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['first_name' => 'Ana', 'last_name' => 'Ruiz']);
    actingAs($agent);

    Livewire::test(DirectBookingForm::class)
        ->set('customerSearch', $customer->display_name)
        ->call('chooseCustomer', $customer->ulid)
        ->set('title', 'Fin de semana en Villa de Leyva')
        ->call('save')
        ->assertHasNoErrors();
    $booking = Booking::query()->sole();

    Livewire::test(DirectItemForm::class, ['booking' => $booking])
        ->set('item.description', 'Posada colonial')
        ->set('item.product_type', ProductType::Hotel->value)
        ->set('item.service_date', '2026-11-20')
        ->set('item.nights', '2')
        ->set('item.ages', '40, 38')
        ->set('item.net_amount', '600000')
        ->call('add')
        ->assertHasNoErrors()
        ->assertRedirect(route('bookings.show', $booking));

    expect($booking->items()->count())->toBe(1);
});

it('validates the direct booking screens and keeps them within scope', function (): void {
    $agent = agent();
    actingAs($agent);
    Livewire::test(DirectBookingForm::class)->call('save')->assertHasErrors(['customerUlid', 'title']);

    $mine = directBooking($agent);
    Livewire::test(DirectItemForm::class, ['booking' => $mine])
        ->set('item.ages', 'muchos')
        ->call('add')
        ->assertHasErrors(['item.description', 'item.product_type', 'item.service_date', 'item.ages', 'item.net_amount']);

    $others = directBooking(agent());
    actingAs($agent)->get(route('bookings.items.create', $others))->assertNotFound();
});

it('applies a customer advance to a later booking within its balance', function (): void {
    $agent = agent();
    $booking = directBooking($agent);
    app(AddDirectItemAction::class)->execute($booking, directHotel(Money::of('500000', 'COP')), CarbonImmutable::now());
    disableCapabilities(Capability::Accounting);
    $customer = Customer::query()->findOrFail($booking->customer_id);
    $record = app(RecordCustomerPaymentAction::class);
    $advance = $record->execute($agent, $customer, PaymentMethod::Cash, Money::of('100000', 'COP'), 'Anticipo', null, CarbonImmutable::now());
    $tooBig = $record->execute($agent, $customer, PaymentMethod::Cash, Money::of('99000000', 'COP'), 'Anticipo grande', null, CarbonImmutable::now());
    $foreign = $record->execute($agent, Customer::factory()->ownedBy($agent)->create(), PaymentMethod::Cash, Money::of('1000', 'COP'), 'Otro cliente', null, CarbonImmutable::now());
    actingAs($agent);

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])
        ->assertSee(__('payments.advances.title'))
        ->assertDontSee('Otro cliente')
        ->call('applyAdvance', $advance->ulid)
        ->assertHasNoErrors()
        ->call('applyAdvance', $tooBig->ulid)
        ->assertHasErrors('advances')
        ->call('applyAdvance', $foreign->ulid)
        ->assertNotFound();

    expect($advance->fresh()?->booking_ulid)->toBe($booking->ulid)
        ->and($tooBig->fresh()?->booking_ulid)->toBeNull()
        ->and(app(BookingAccounts::class)->account($booking->ulid)->customerId)->toBe($customer->id)
        ->and(Payment::query()->where('booking_ulid', $booking->ulid)->count())->toBe(1);
});
