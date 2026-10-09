<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\Finance\Actions\OpenCashSessionAction;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Payments\Actions\RecordCustomerPaymentAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Livewire\CustomerPaymentsScreen;
use App\Modules\Payments\Models\Payment;
use App\Modules\Shared\Enums\Capability;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
});

it('records a cash advance without a booking into the open cash', function (): void {
    Event::fake([PaymentReceived::class]);
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::zero('COP'), CarbonImmutable::now());

    $payment = app(RecordCustomerPaymentAction::class)->execute($agent, $customer, PaymentMethod::Cash, Money::of('200000', 'COP'), 'Anticipo viaje a San Andrés', null, CarbonImmutable::now());

    expect($payment->booking_ulid)->toBeNull()
        ->and($payment->customer_id)->toBe($customer->id)
        ->and($payment->status)->toBe(PaymentStatus::Approved)
        ->and(CashMovement::query()->count())->toBe(1);
    Event::assertDispatched(PaymentReceived::class, static fn(PaymentReceived $event): bool => $event->bookingUlid === null);
});

it('records advances without cash register when accounting is off', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    disableCapabilities(Capability::Accounting);

    app(RecordCustomerPaymentAction::class)->execute($agent, $customer, PaymentMethod::Cash, Money::of('1000', 'COP'), 'Venta externa', null, CarbonImmutable::now());

    expect(Payment::query()->count())->toBe(1)->and(CashMovement::query()->count())->toBe(0);
});

it('refuses payment links and non positive amounts without a booking', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    $record = app(RecordCustomerPaymentAction::class);

    expect(fn() => $record->execute($agent, $customer, PaymentMethod::OnlineLink, Money::of('1', 'COP'), 'x', null, CarbonImmutable::now()))
        ->toThrow(PaymentRuleViolation::class, __('payments.errors.method_not_allowed_without_booking'))
        ->and(fn() => $record->execute($agent, $customer, PaymentMethod::Cash, Money::zero('COP'), 'x', null, CarbonImmutable::now()))
        ->toThrow(PaymentRuleViolation::class, __('payments.errors.invalid_amount'));
});

it('registers transfers from the screen and lets finance validate them, with bookings off', function (): void {
    disableCapabilities(Capability::Bookings, Capability::Portals);
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['first_name' => 'Pedro', 'last_name' => 'Ruiz']);
    actingAs($agent);

    Livewire::test(CustomerPaymentsScreen::class)
        ->set('customerSearch', $customer->display_name)
        ->call('chooseCustomer', $customer->ulid)
        ->set('form.method', PaymentMethod::BankTransfer->value)
        ->set('form.amount', '500000')
        ->set('form.concept', 'Anticipo')
        ->call('register')
        ->assertHasErrors('form.reference')
        ->set('form.reference', 'TRX-77')
        ->call('register')
        ->assertHasNoErrors();

    $payment = Payment::query()->sole();
    expect($payment->status)->toBe(PaymentStatus::Pending);

    actingAs(financeUser());
    Livewire::test(CustomerPaymentsScreen::class)->call('validateTransfer', $payment->ulid, true);
    expect($payment->fresh()?->status)->toBe(PaymentStatus::Approved);
});

it('keeps validation for finance and payments within scope', function (): void {
    $agent = agent();
    $other = agent();
    $payment = app(RecordCustomerPaymentAction::class)->execute($other, Customer::factory()->ownedBy($other)->create(), PaymentMethod::BankTransfer, Money::of('1', 'COP'), 'x', 'REF-OTRO-ASESOR', CarbonImmutable::now());
    actingAs($agent);

    Livewire::test(CustomerPaymentsScreen::class)->assertDontSee('REF-OTRO-ASESOR')->call('validateTransfer', $payment->ulid, true)->assertForbidden();
    Livewire::test(CustomerPaymentsScreen::class)->call('register')->assertHasErrors(['customerUlid', 'form.amount', 'form.concept']);
});
