<?php

declare(strict_types=1);

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Integrations\Adapters\FakePayments\FakePaymentGateway;
use App\Modules\Payments\Actions\CreatePaymentLinkAction;
use App\Modules\Payments\Actions\ExpirePaymentLinksAction;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Actions\ValidateTransferAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentGatewayEvent;
use App\Modules\Payments\Services\PaymentLedger;
use App\Modules\Pricing\Models\MarkupRule;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\call;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Expediente de familia (hotel 2026-11-10) con su total de venta. */
function payableBooking(?User $agent = null): Booking
{
    return familyBooking($agent ?? agent());
}

function accountOf(Booking $booking): App\Modules\Bookings\Data\BookingAccount
{
    return app(BookingAccounts::class)->account($booking->ulid);
}

function cop(string $amount): Money
{
    return Money::of($amount, 'COP');
}

function webhook(string $body, ?string $signature = null): Illuminate\Testing\TestResponse
{
    return call('POST', route('payments.webhook', FakePaymentGateway::KEY), [], [], [], [
        'HTTP_' . str_replace('-', '_', mb_strtoupper(FakePaymentGateway::SIGNATURE_HEADER)) => $signature ?? FakePaymentGateway::sign($body),
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

it('summarises total, paid, pending, balance and due date', function (): void {
    $booking = payableBooking();
    $account = accountOf($booking);
    $record = app(RecordPaymentAction::class);
    $actor = User::query()->findOrFail($booking->owner_id);

    $record->execute($actor, $account, PaymentMethod::Cash, cop('100000'), null, null, CarbonImmutable::now());
    $record->execute($actor, $account, PaymentMethod::BankTransfer, cop('50000'), 'TRX-1', null, CarbonImmutable::now());
    $summary = app(PaymentLedger::class)->summary($account, CarbonImmutable::now());

    expect((string) $summary->paid->getAmount())->toBe('100000.00')
        ->and((string) $summary->pending->getAmount())->toBe('50000.00')
        ->and($summary->balance->isEqualTo($account->saleTotal->minus(cop('100000'))))->toBeTrue()
        ->and($summary->dueDate?->toDateString())->toBe('2026-10-26')
        ->and($summary->isOverdue)->toBeFalse();

    $this->travelTo(CarbonImmutable::parse('2026-10-27 12:00:00'));
    expect(app(PaymentLedger::class)->summary($account, CarbonImmutable::now())->isOverdue)->toBeTrue();
});

it('never collects more than the balance minus pending payments', function (): void {
    $booking = payableBooking();
    $account = accountOf($booking);
    $actor = User::query()->findOrFail($booking->owner_id);
    $record = app(RecordPaymentAction::class);
    $record->execute($actor, $account, PaymentMethod::BankTransfer, $account->saleTotal->minus(cop('1000')), 'TRX', null, CarbonImmutable::now());

    expect(fn() => $record->execute($actor, $account, PaymentMethod::Cash, cop('1000.01'), null, null, CarbonImmutable::now()))->toThrow(PaymentRuleViolation::class)
        ->and(fn() => app(CreatePaymentLinkAction::class)->execute($actor, $account, cop('2000'), CarbonImmutable::now()))->toThrow(PaymentRuleViolation::class);
    expect($record->execute($actor, $account, PaymentMethod::Cash, cop('1000'), null, null, CarbonImmutable::now())->status)->toBe(PaymentStatus::Approved);
});

it('lets finance validate or reject pending transfers once', function (): void {
    $booking = payableBooking();
    $actor = User::query()->findOrFail($booking->owner_id);
    $transfer = app(RecordPaymentAction::class)->execute($actor, accountOf($booking), PaymentMethod::BankTransfer, cop('50000'), 'TRX-9', null, CarbonImmutable::now());
    $validate = app(ValidateTransferAction::class);

    $validated = $validate->execute(userWithRole(Role::Finance), $transfer, true, 'Visto en extracto', CarbonImmutable::now());

    expect($validated->status)->toBe(PaymentStatus::Approved)
        ->and($validated->approved_at)->not->toBeNull()
        ->and(fn() => $validate->execute(userWithRole(Role::Finance), $validated, false, null, CarbonImmutable::now()))->toThrow(PaymentRuleViolation::class);
});

it('keeps approved payments immutable', function (): void {
    $booking = payableBooking();
    $cash = app(RecordPaymentAction::class)->execute(User::query()->findOrFail($booking->owner_id), accountOf($booking), PaymentMethod::Cash, cop('1000'), null, null, CarbonImmutable::now());

    expect(fn() => $cash->update(['amount_minor' => 1]))->toThrow(LogicException::class)
        ->and(fn() => $cash->delete())->toThrow(LogicException::class);
});

it('approves link payments through a signed and idempotent webhook', function (): void {
    $booking = payableBooking();
    $link = app(CreatePaymentLinkAction::class)->execute(User::query()->findOrFail($booking->owner_id), accountOf($booking), cop('80000'), CarbonImmutable::now());
    $body = (string) json_encode(['event_id' => 'evt-1', 'reference' => $link->gateway_reference, 'outcome' => 'approved']);

    expect($link->status)->toBe(PaymentStatus::Pending)->and($link->link_url)->toContain('fake-checkout');

    webhook($body)->assertStatus(202);
    webhook($body)->assertStatus(202);

    expect($link->fresh()?->status)->toBe(PaymentStatus::Approved)
        ->and(PaymentGatewayEvent::query()->count())->toBe(1);

    // Un evento posterior de rechazo no revierte un pago ya aprobado.
    webhook((string) json_encode(['event_id' => 'evt-2', 'reference' => $link->gateway_reference, 'outcome' => 'rejected']))->assertStatus(202);
    expect($link->fresh()?->status)->toBe(PaymentStatus::Approved);
});

it('rejects webhooks with invalid signature, malformed body or unknown gateway', function (): void {
    $body = (string) json_encode(['event_id' => 'evt-1', 'reference' => 'x', 'outcome' => 'approved']);

    webhook($body, 'firma-falsa')->assertStatus(400);
    webhook('{no es json', FakePaymentGateway::sign('{no es json'))->assertStatus(400);
    webhook((string) json_encode(['event_id' => 'e', 'reference' => 'r', 'outcome' => 'pending']))->assertStatus(400);
    call('POST', route('payments.webhook', 'wompi'), [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)->assertStatus(400);
    expect(PaymentGatewayEvent::query()->count())->toBe(0);
});

it('ignores events for unknown payment references without failing', function (): void {
    webhook((string) json_encode(['event_id' => 'evt-x', 'reference' => 'FAKEPAY-NOEXISTE', 'outcome' => 'approved']))->assertStatus(202);

    expect(PaymentGatewayEvent::query()->sole()->processed_at)->not->toBeNull();
});

it('completes a payment from the simulated checkout page', function (): void {
    $booking = payableBooking();
    $link = app(CreatePaymentLinkAction::class)->execute(User::query()->findOrFail($booking->owner_id), accountOf($booking), cop('80000'), CarbonImmutable::now());

    get((string) $link->link_url)->assertOk()->assertSee(__('integrations.fake_checkout.approve'));
    get(route('integrations.fake-checkout', ['reference' => 'x', 'amount' => 1, 'currency' => 'COP']))->assertForbidden();

    $complete = Illuminate\Support\Facades\URL::signedRoute('integrations.fake-checkout.complete');
    post($complete, ['reference' => $link->gateway_reference, 'amount' => 8000000, 'currency' => 'COP', 'outcome' => 'rejected'])
        ->assertOk()->assertSee(PaymentStatus::Rejected->label());

    expect($link->fresh()?->status)->toBe(PaymentStatus::Rejected);
});

it('expires unpaid links and frees their amount', function (): void {
    $booking = payableBooking();
    $link = app(CreatePaymentLinkAction::class)->execute(User::query()->findOrFail($booking->owner_id), accountOf($booking), cop('80000'), CarbonImmutable::now());
    $this->travelTo(CarbonImmutable::now()->addHours(config()->integer('travel.payments.link_ttl_hours') + 1));

    expect(app(ExpirePaymentLinksAction::class)->execute(CarbonImmutable::now()))->toBe(1)
        ->and($link->fresh()?->status)->toBe(PaymentStatus::Expired)
        ->and(app(PaymentLedger::class)->summary(accountOf($booking), CarbonImmutable::now())->pending->isZero())->toBeTrue();
});

it('shows the booking payments only within the scope', function (): void {
    $agent = agent();
    $booking = payableBooking($agent);

    get(route('payments.booking', $booking->ulid))->assertRedirect(route('login'));
    actingAs(agent())->get(route('payments.booking', $booking->ulid))->assertNotFound();
    actingAs($agent)->get(route('payments.booking', 'no-existe'))->assertNotFound();
    actingAs($agent)->get(route('payments.booking', $booking->ulid))->assertOk()->assertSee(__('payments.summary.balance'));
    actingAs($agent)->get(route('bookings.show', $booking))->assertSee(route('payments.booking', $booking->ulid));
});

it('registers payments and links from the screen', function (): void {
    $agent = agent();
    $booking = payableBooking($agent);
    actingAs($agent);

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])
        ->set('form.method', PaymentMethod::BankTransfer->value)
        ->set('form.amount', '50000')
        ->call('register')
        ->assertHasErrors('form.reference')
        ->set('form.reference', 'TRX-77')
        ->call('register')
        ->assertHasNoErrors()
        ->assertSee(PaymentStatus::Pending->label())
        ->assertDontSee(__('payments.list.approve'))
        ->set('form.method', PaymentMethod::OnlineLink->value)
        ->set('form.amount', '1000')
        ->call('register')
        ->assertSee('fake-checkout')
        ->set('form.method', PaymentMethod::Cash->value)
        ->set('form.amount', '999999999')
        ->call('register')
        ->assertHasErrors('form.amount')
        ->call('validateTransfer', Payment::query()->where('method', PaymentMethod::BankTransfer)->value('ulid'), true)
        ->assertForbidden();
});

it('lets finance validate transfers from the screen', function (): void {
    $booking = payableBooking();
    $transfer = app(RecordPaymentAction::class)->execute(User::query()->findOrFail($booking->owner_id), accountOf($booking), PaymentMethod::BankTransfer, cop('50000'), 'TRX', null, CarbonImmutable::now());
    actingAs(userWithRole(Role::Finance));

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])
        ->call('validateTransfer', $transfer->ulid, true)
        ->assertSee(PaymentStatus::Approved->label())
        ->call('validateTransfer', $transfer->ulid, false)
        ->assertHasErrors('payments')
        ->call('validateTransfer', 'otro', true)
        ->assertNotFound();
});

it('says when there is nothing left to collect', function (): void {
    $booking = payableBooking();
    $account = accountOf($booking);
    app(RecordPaymentAction::class)->execute(User::query()->findOrFail($booking->owner_id), $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])->assertSee(__('payments.register.nothing_to_collect'));
});

it('labels payment enums', function (): void {
    foreach ([...PaymentMethod::cases(), ...PaymentStatus::cases()] as $case) {
        expect($case->label())->not->toStartWith('payments.');
    }
});
