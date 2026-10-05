<?php

declare(strict_types=1);

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Finance\Actions\CloseCashSessionAction;
use App\Modules\Finance\Actions\OpenCashSessionAction;
use App\Modules\Finance\Actions\RecordCashExpenseAction;
use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Livewire\CashRegisterScreen;
use App\Modules\Finance\Models\CashMovement;
use App\Modules\Finance\Models\CashSession;
use App\Modules\Finance\Services\CashDesk;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Pricing\Models\MarkupRule;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function cashSessionOf(User $user): CashSession
{
    return app(CashDesk::class)->openSessionFor((int) $user->branch_id) ?? throw new RuntimeException('sin caja');
}

it('opens one cash session per branch', function (): void {
    $agent = agent();
    $session = app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::of('100000', 'COP'), CarbonImmutable::now());

    expect($session->status)->toBe(CashSessionStatus::Open)
        ->and(fn() => app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::of('1', 'COP'), CarbonImmutable::now()))
        ->toThrow(FinanceRuleViolation::class, __('finance.errors.cash_session_already_open'));
});

it('puts cash payments into the open cash of the receiver branch', function (): void {
    $booking = familyBooking(agent());
    $receiver = User::query()->findOrFail($booking->owner_id);

    $payment = app(RecordPaymentAction::class)->execute($receiver, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::Cash, Money::of('50000', 'COP'), null, null, CarbonImmutable::now());

    $movement = CashMovement::query()->sole();
    expect($movement->payment_ulid)->toBe($payment->ulid)
        ->and($movement->type)->toBe(CashMovementType::Income)
        ->and($movement->cash_session_id)->toBe(cashSessionOf($receiver)->id)
        ->and((string) app(CashDesk::class)->expected(cashSessionOf($receiver))->getAmount())->toBe('50000.00');
});

it('refuses cash payments without an open cash and keeps nothing', function (): void {
    $booking = familyBooking(agent());
    $receiver = User::query()->findOrFail($booking->owner_id);
    app(CloseCashSessionAction::class)->execute($receiver, cashSessionOf($receiver), Money::zero('COP'), null, CarbonImmutable::now());

    expect(fn() => app(RecordPaymentAction::class)->execute($receiver, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::Cash, Money::of('1000', 'COP'), null, null, CarbonImmutable::now()))
        ->toThrow(FinanceRuleViolation::class, __('finance.errors.cash_session_closed'));
    expect(Payment::query()->count())->toBe(0);

    // Las transferencias no pasan por caja.
    app(RecordPaymentAction::class)->execute($receiver, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::BankTransfer, Money::of('1000', 'COP'), 'TRX', null, CarbonImmutable::now());
    expect(Payment::query()->count())->toBe(1);
});

it('closes with the count and records the difference', function (int $counted, int $difference): void {
    $agent = agent();
    $session = app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::of('100000', 'COP'), CarbonImmutable::now());
    app(RecordCashExpenseAction::class)->execute($agent, $session, Money::of('20000', 'COP'), 'Mensajería');

    $closed = app(CloseCashSessionAction::class)->execute($agent, $session, Money::of((string) $counted, 'COP'), 'Arqueo', CarbonImmutable::now());

    expect($closed->status)->toBe(CashSessionStatus::Closed)
        ->and($closed->expected_amount_minor)->toBe(8000000)
        ->and($closed->difference_minor)->toBe($difference * 100)
        ->and($closed->open_branch_key)->toBeNull()
        ->and(fn() => app(CloseCashSessionAction::class)->execute($agent, $closed, Money::zero('COP'), null, CarbonImmutable::now()))->toThrow(FinanceRuleViolation::class);
})->with([
    'exact' => [80000, 0],
    'shortage' => [79000, -1000],
    'surplus' => [80500, 500],
]);

it('lets the branch open a new cash after closing', function (): void {
    $agent = agent();
    $first = app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::zero('COP'), CarbonImmutable::now());
    app(CloseCashSessionAction::class)->execute($agent, $first, Money::zero('COP'), null, CarbonImmutable::now());

    expect(app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::zero('COP'), CarbonImmutable::now())->id)->not->toBe($first->id);
});

it('refuses other currencies and keeps cash records immutable', function (): void {
    $agent = agent();
    $session = app(OpenCashSessionAction::class)->execute($agent, (int) $agent->branch_id, Money::zero('COP'), CarbonImmutable::now());

    expect(fn() => app(RecordCashExpenseAction::class)->execute($agent, $session, Money::of('10', 'USD'), 'x'))->toThrow(FinanceRuleViolation::class);

    $movement = app(RecordCashExpenseAction::class)->execute($agent, $session, Money::of('10', 'COP'), 'x');
    expect(fn() => $movement->update(['amount_minor' => 1]))->toThrow(LogicException::class)
        ->and(fn() => $movement->delete())->toThrow(LogicException::class)
        ->and(fn() => $session->delete())->toThrow(LogicException::class);
});

it('runs the daily cash from the screen', function (): void {
    $agent = agent();
    actingAs($agent);

    Livewire::test(CashRegisterScreen::class)
        ->assertSee(__('finance.cash.closed_hint'))
        ->call('open')
        ->assertHasErrors('form.opening')
        ->set('form.opening', '100000')
        ->call('open')
        ->assertHasNoErrors()
        ->assertSee(__('finance.cash.close_title'))
        ->set('form.expense_amount', '15000')
        ->set('form.expense_description', 'Taxi a la notaría')
        ->call('expense')
        ->assertSee('Taxi a la notaría')
        ->set('form.counted', '85000')
        ->call('close')
        ->assertHasNoErrors()
        ->assertSee(__('finance.cash.open_title'))
        ->assertSee(__('finance.cash.difference', ['amount' => app(App\Modules\Shared\Money\MoneyPresenter::class)->format(Money::zero('COP'))]))
        ->call('close')
        ->assertHasErrors('form.counted');
});

it('lets users with full scope choose the branch and reports errors', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $other = Branch::factory()->create(['name' => 'Cali Norte']);
    actingAs($owner);

    Livewire::test(CashRegisterScreen::class)
        ->assertSee('Cali Norte')
        ->set('branch', (string) $other->id)
        ->set('form.opening', '0')
        ->call('open')
        ->assertHasNoErrors()
        ->set('form.opening', '0')
        ->call('open')
        ->assertHasErrors('form.opening')
        ->set('form.expense_amount', '10')
        ->set('form.expense_description', 'x')
        ->call('expense')
        ->assertSee('x');

    expect(app(CashDesk::class)->openSessionFor($other->id))->not->toBeNull();
});

it('blocks users without branch', function (): void {
    $agent = agent();
    $agent->branch_id = null;
    $agent->save();

    actingAs($agent)->get(route('finance.cash'))->assertForbidden();
});

it('labels cash enums', function (): void {
    foreach ([...CashSessionStatus::cases(), ...CashMovementType::cases()] as $case) {
        expect($case->label())->not->toStartWith('finance.');
    }
});
