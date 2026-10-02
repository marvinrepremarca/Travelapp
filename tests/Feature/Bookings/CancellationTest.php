<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\SetCancellationPolicyAction;
use App\Modules\Bookings\Data\CancellationPolicy;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Pricing\Models\MarkupRule;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Política típica: gratis hasta 30 días antes, 50 % desde 15 días, 100 % desde 7 días. */
function standardPolicy(): CancellationPolicy
{
    return new CancellationPolicy(false, [
        ['days_before' => 7, 'rate_basis_points' => 10000],
        ['days_before' => 30, 'rate_basis_points' => 0],
        ['days_before' => 15, 'rate_basis_points' => 5000],
    ]);
}

it('picks the tier closest to the service date', function (int $daysBefore, string $penalty): void {
    expect((string) standardPolicy()->penaltyFor(Money::of('1000000', 'COP'), $daysBefore)->getAmount())->toBe($penalty);
})->with([
    'more than 30 days' => [45, '0.00'],
    'exactly 30 days' => [30, '0.00'],
    '20 days' => [20, '0.00'],
    'exactly 15 days' => [15, '500000.00'],
    '10 days' => [10, '500000.00'],
    'exactly 7 days' => [7, '1000000.00'],
    'service day' => [0, '1000000.00'],
    'after the service' => [-2, '1000000.00'],
]);

it('charges everything when non refundable and nothing without tiers', function (): void {
    expect((string) (new CancellationPolicy(true, []))->penaltyFor(Money::of('800', 'USD'), 90)->getAmount())->toBe('800.00')
        ->and((new CancellationPolicy(false, []))->penaltyFor(Money::of('800', 'USD'), 1)->isZero())->toBeTrue()
        ->and(standardPolicy()->toArray()['tiers'][0]['days_before'])->toBe(30);
});

it('records the penalty when a confirmed service is cancelled', function (): void {
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    app(SetCancellationPolicyAction::class)->execute($hotel, standardPolicy());
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
    // Servicio el 2026-11-10; hoy 2026-10-28 en Bogotá = 13 días antes → 50 %.
    $this->travelTo(CarbonImmutable::parse('2026-10-28 20:00:00', 'America/Bogota'));

    app(ChangeItemStatusAction::class)->execute($hotel->fresh() ?? $hotel, BookingItemStatus::Cancelled, 'El cliente desiste', CarbonImmutable::now());

    $hotel->refresh();
    expect($hotel->penalty_amount_minor)->toBe(intdiv($hotel->sale_amount_minor, 2))
        ->and($hotel->penaltyAmount()?->getCurrency()->getCurrencyCode())->toBe('COP');
});

it('uses the agency date, not UTC, to count the days before', function (): void {
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    app(SetCancellationPolicyAction::class)->execute($hotel, standardPolicy());
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
    // 2026-10-27 22:00 en Bogotá ya es 2026-10-28 en UTC: siguen siendo 14 días (tramo 50 %), no 13.
    $this->travelTo(CarbonImmutable::parse('2026-10-27 22:00:00', 'America/Bogota'));

    expect(app(App\Modules\Bookings\Services\CancellationPenaltyCalculator::class)->daysBefore($hotel->fresh() ?? $hotel, CarbonImmutable::now()))->toBe(14);
});

it('does not charge penalties on unconfirmed services or without policy', function (): void {
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    app(SetCancellationPolicyAction::class)->execute($hotel, new CancellationPolicy(true, []));

    app(ChangeItemStatusAction::class)->execute($hotel, BookingItemStatus::Cancelled, 'x', CarbonImmutable::now());

    expect($hotel->fresh()?->penalty_amount_minor)->toBeNull();
});

it('does not set policies on closed services', function (): void {
    $hotel = hotelOf(familyBooking(agent()));
    app(ChangeItemStatusAction::class)->execute($hotel, BookingItemStatus::Cancelled, 'x', CarbonImmutable::now());

    app(SetCancellationPolicyAction::class)->execute($hotel->fresh() ?? $hotel, standardPolicy());
})->throws(BookingRuleViolation::class);

it('edits the policy and warns about the penalty from the booking screen', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    $hotel = hotelOf($booking);
    actingAs($agent);

    $screen = Livewire::test(BookingShow::class, ['booking' => $booking])
        ->call('editPolicy', $hotel->ulid)
        ->set('policy.tiers', 'treinta')
        ->call('savePolicy')
        ->assertHasErrors('policy.tiers')
        ->set('policy.tiers', '30:0, 7:150')
        ->call('savePolicy')
        ->assertHasErrors('policy.tiers')
        ->set('policy.tiers', '30:0, 15:50, 7:100')
        ->call('savePolicy')
        ->assertHasNoErrors()
        ->assertSee(__('bookings.policy.tier', ['days' => 15, 'rate' => '50.00']))
        ->call('editPolicy', $hotel->ulid)
        ->assertSet('policy.tiers', '30:0.00, 15:50.00, 7:100.00')
        ->set('policy.non_refundable', true)
        ->call('savePolicy')
        ->assertSee(__('bookings.policy.non_refundable'));

    app(ConfirmItemAction::class)->execute($hotel->fresh() ?? $hotel, 'HCR-1', null, CarbonImmutable::now());
    $fresh = $booking->fresh() ?? $booking;
    $penalty = app(App\Modules\Shared\Money\MoneyPresenter::class)->format((BookingItem::query()->sole())->saleAmount());

    Livewire::test(BookingShow::class, ['booking' => $fresh])
        ->assertSee(__('bookings.policy.penalty_today', ['amount' => $penalty]))
        ->call('manage', $hotel->ulid)
        ->set('action.status', BookingItemStatus::Cancelled->value)
        ->assertSee(__('bookings.policy.cancel_warning', ['amount' => $penalty]))
        ->set('action.note', 'Desiste')
        ->call('apply')
        ->assertSee(__('bookings.policy.penalty_charged', ['amount' => $penalty]));

    unset($screen);
});
