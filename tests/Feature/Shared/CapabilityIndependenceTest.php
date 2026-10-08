<?php

declare(strict_types=1);

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Bookings\Services\NullBookingProfitLines;
use App\Modules\Communications\Contracts\CustomerNotices;
use App\Modules\Communications\Services\NullCustomerNotices;
use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Services\NullCashRegister;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Contracts\CustomerPayments;
use App\Modules\Payments\Contracts\ReceivedPayments;
use App\Modules\Payments\Contracts\UpcomingBalances;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Services\NullReceivedPayments;
use App\Modules\Payments\Services\NullUpcomingBalances;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Contratos que una capacidad ofrece a otras y su implementación cuando está apagada (ADR-0007). */
dataset('owned contracts', [
    'caja' => [Capability::Accounting, CashRegister::class, NullCashRegister::class],
    'abonos recibidos' => [Capability::Collections, ReceivedPayments::class, NullReceivedPayments::class],
    'saldos por vencer' => [Capability::Collections, UpcomingBalances::class, NullUpcomingBalances::class],
    'ventas por expediente' => [Capability::Bookings, BookingProfitLines::class, NullBookingProfitLines::class],
    'avisos al cliente' => [Capability::Messaging, CustomerNotices::class, NullCustomerNotices::class],
]);

it('hands consumers the null implementation only while the owner capability is off', function (Capability $owner, string $contract, string $null): void {
    expect(app($contract))->not->toBeInstanceOf($null);

    disableCapabilities($owner);

    expect(app($contract))->toBeInstanceOf($null);
})->with('owned contracts');

it('records cash payments without a cash register when accounting is off', function (): void {
    $booking = familyBooking(agent());
    $receiver = User::query()->findOrFail($booking->owner_id);
    disableCapabilities(Capability::Accounting);

    app(RecordPaymentAction::class)->execute($receiver, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::Cash, Money::of('1000', 'COP'), null, null, CarbonImmutable::now());

    expect(Payment::query()->count())->toBe(1)
        ->and(CashMovement::query()->count())->toBe(0);
});

it('offers no payment link to travelers when collections is off', function (): void {
    $booking = familyBooking(agent());
    disableCapabilities(Capability::Collections);

    expect(app(CustomerPayments::class)->payBalanceUrl($booking->ulid))->toBeNull();
});

it('shows links to a disabled capability as plain text', function (): void {
    $booking = familyBooking(agent());
    $owner = User::query()->findOrFail($booking->owner_id);
    $paymentsUrl = route('payments.booking', $booking->ulid);
    actingAs($owner)->get(route('bookings.show', $booking))->assertOk()->assertSee($paymentsUrl, false);

    disableCapabilities(Capability::Collections);

    actingAs($owner)->get(route('bookings.show', $booking))->assertOk()->assertDontSee($paymentsUrl, false)->assertSee(__('payments.link'));
});

it('quotes only supplier or manual services when own product is off', function (): void {
    $agent = agent();
    $quote = Quote::factory()->ownedBy($agent)->create(['customer_id' => Customer::factory()->ownedBy($agent)]);
    disableCapabilities(Capability::OwnProduct);
    actingAs($agent);

    Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->assertSet('item.kind', QuoteItemKind::Manual->value)
        ->assertViewHas('catalogProducts', [])
        ->assertViewHas('kinds', fn(array $kinds): bool => ! in_array(QuoteItemKind::Catalog, $kinds, true))
        ->set('item.kind', QuoteItemKind::Catalog->value)
        ->call('addItem')
        ->assertHasErrors('item.kind');
});

it('requires own product to operate departures', function (): void {
    disableCapabilities(Capability::OwnProduct);

    expect(app(Capabilities::class)->problems())->toContain(__('capabilities.missing_requirement', [
        'capability' => Capability::Operations->label(),
        'required' => Capability::OwnProduct->label(),
    ]));
});

it('keeps the agency owner dashboard working with every capability off', function (): void {
    config()->set('capabilities.enabled', array_fill_keys(array_map(static fn(Capability $c): string => $c->value, Capability::cases()), false));
    app()->forgetInstance(Capabilities::class);

    actingAs(userWithRole(Role::AgencyOwner))->get(route('dashboard'))->assertOk();
    actingAs(userWithRole(Role::AgencyOwner))->get(route('customers.index'))->assertOk();
});
