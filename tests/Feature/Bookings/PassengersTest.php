<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\AssignPassengersAction;
use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Customers\Models\Traveler;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Enums\PassengerType;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function travelerBorn(Booking $booking, string $name, string $birthDate): Traveler
{
    return Traveler::factory()->create(['customer_id' => $booking->customer_id, 'first_name' => $name, 'birth_date' => $birthDate]);
}

it('assigns travelers whose types at the service date match the quote', function (): void {
    $booking = familyBooking(agent());
    $mother = travelerBorn($booking, 'Laura', '1986-05-01');
    $son = travelerBorn($booking, 'Tomás', '2018-03-15');

    app(AssignPassengersAction::class)->execute(hotelOf($booking), [$mother->ulid, $son->ulid]);

    $passengers = hotelOf($booking)->passengers()->orderBy('age_at_service')->get();
    expect($passengers)->toHaveCount(2)
        ->and($passengers[0]->age_at_service)->toBe(8)
        ->and($passengers[0]->passenger_type)->toBe(PassengerType::Child)
        ->and($passengers[1]->passenger_type)->toBe(PassengerType::Adult);
});

it('uses the age at the service date, not today', function (): void {
    $booking = familyBooking(agent());
    $mother = travelerBorn($booking, 'Laura', '1986-05-01');
    // Hoy tiene 11 (niño) pero cumple 12 antes del servicio: viaja como adulto.
    $turnsTwelve = travelerBorn($booking, 'Sara', '2014-11-01');

    expect(fn() => app(AssignPassengersAction::class)->execute(hotelOf($booking), [$mother->ulid, $turnsTwelve->ulid]))
        ->toThrow(BookingRuleViolation::class, __('bookings.errors.passengers_do_not_match', [
            'quoted' => '1 ' . PassengerType::Adult->label() . ', 1 ' . PassengerType::Child->label(),
            'assigned' => '2 ' . PassengerType::Adult->label(),
        ]));
});

it('rejects a different number of passengers and travelers of other customers', function (): void {
    $booking = familyBooking(agent());
    $mother = travelerBorn($booking, 'Laura', '1986-05-01');
    $stranger = Traveler::factory()->create(['birth_date' => '2018-03-15']);
    $assign = app(AssignPassengersAction::class);

    expect(fn() => $assign->execute(hotelOf($booking), [$mother->ulid]))->toThrow(BookingRuleViolation::class)
        ->and(fn() => $assign->execute(hotelOf($booking), [$mother->ulid, $stranger->ulid]))->toThrow(BookingRuleViolation::class, __('bookings.errors.traveler_not_of_customer'));
});

it('replaces the previous assignment and records it in the audit log', function (): void {
    $booking = familyBooking(agent());
    $mother = travelerBorn($booking, 'Laura', '1986-05-01');
    $father = travelerBorn($booking, 'Andrés', '1984-02-01');
    $son = travelerBorn($booking, 'Tomás', '2018-03-15');
    $assign = app(AssignPassengersAction::class);

    $assign->execute(hotelOf($booking), [$mother->ulid, $son->ulid]);
    $assign->execute(hotelOf($booking), [$father->ulid, $son->ulid]);

    expect(hotelOf($booking)->passengers()->pluck('traveler_id')->sort()->values()->all())->toBe(collect([$father->id, $son->id])->sort()->values()->all())
        ->and(Spatie\Activitylog\Models\Activity::query()->where('description', 'passengers_assigned')->count())->toBe(2);
});

it('does not assign passengers to closed services', function (): void {
    $booking = familyBooking(agent());
    app(ChangeItemStatusAction::class)->execute(hotelOf($booking), BookingItemStatus::Cancelled, 'x', CarbonImmutable::now());

    app(AssignPassengersAction::class)->execute(hotelOf($booking), []);
})->throws(BookingRuleViolation::class);

it('assigns passengers from the booking screen', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    $mother = travelerBorn($booking, 'Laura', '1986-05-01');
    $son = travelerBorn($booking, 'Tomás', '2018-03-15');
    actingAs($agent);

    Livewire::test(BookingShow::class, ['booking' => $booking])
        ->assertSee(__('bookings.passengers.pending_count', ['count' => 1]))
        ->call('editPassengers', hotelOf($booking)->ulid)
        ->assertSee('Tomás')
        ->set('selectedTravelers', [$mother->ulid])
        ->call('savePassengers')
        ->assertHasErrors('selectedTravelers')
        ->set('selectedTravelers', [$mother->ulid, $son->ulid])
        ->call('savePassengers')
        ->assertHasNoErrors()
        ->assertDontSee(__('bookings.passengers.missing'))
        ->call('editPassengers', hotelOf($booking)->ulid)
        ->assertSet('selectedTravelers', fn(array $selected): bool => count($selected) === 2);
});

it('tells the agent when the customer has no travelers', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    actingAs($agent);

    Livewire::test(BookingShow::class, ['booking' => $booking])
        ->call('editPassengers', hotelOf($booking)->ulid)
        ->assertSee(__('bookings.passengers.none'));
});
