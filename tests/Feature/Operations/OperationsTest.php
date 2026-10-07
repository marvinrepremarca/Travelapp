<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\AssignPassengersAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Crm\Models\Traveler;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Operations\Actions\AssignDepartureResourcesAction;
use App\Modules\Operations\Exceptions\OperationsRuleViolation;
use App\Modules\Operations\Livewire\DeparturesBoard;
use App\Modules\Operations\Livewire\DepartureShow;
use App\Modules\Operations\Livewire\GuideForm;
use App\Modules\Operations\Livewire\ResourcesIndex;
use App\Modules\Operations\Livewire\VehicleForm;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Operations\Models\Guide;
use App\Modules\Operations\Models\Vehicle;
use App\Modules\Pricing\Models\MarkupRule;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 08:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function operator(): User
{
    return userWithRole(Role::Operations);
}

/** Salida de un tour de 4 horas a las 09:00 del día indicado. */
function tourDeparture(string $date = '2026-11-10', string $time = '09:00', int $capacity = 20): CatalogDeparture
{
    $product = CatalogProduct::factory()->create(['name' => 'Tour murallas ' . $date . ' ' . $time, 'duration_minutes' => 240]);

    return app(AddDepartureAction::class)->execute($product, CarbonImmutable::parse($date), $time, $capacity);
}

/** Expediente con un servicio confirmado en la salida y dos viajeros asignados. */
function passengersOn(CatalogDeparture $departure): App\Modules\Bookings\Models\Booking
{
    $booking = familyBooking(agent());
    $item = hotelOf($booking);
    // La familia se cotizó con un adulto de 40 años y un niño de 8 (fecha del servicio 2026-11-10).
    $travelers = collect(['1986-01-15', '2018-03-20'])->map(static fn(string $birthDate): Traveler => Traveler::factory()->create([
        'customer_id' => $booking->customer_id,
        'birth_date' => $birthDate,
        'passport_number' => 'PA1234567',
    ]));
    app(AssignPassengersAction::class)->execute($item, $travelers->pluck('ulid')->all());
    $item->catalog_departure_ulid = $departure->ulid;
    $item->status = BookingItemStatus::Confirmed;
    $item->save();
    $departure->reserved_seats += 2;
    $departure->save();

    return $booking;
}

it('builds the manifest with masked passports and contact', function (): void {
    $departure = tourDeparture();
    $booking = passengersOn($departure);

    $manifest = app(App\Modules\Bookings\Contracts\DepartureManifests::class)->passengersOf($departure->ulid);

    expect($manifest)->toHaveCount(2)
        ->and($manifest[0]->bookingNumber)->toBe((string) $booking->number)
        ->and($manifest[0]->maskedDocument)->not->toContain('1234567')
        ->and($manifest[0]->contactPhone)->not->toBeNull()
        ->and(app(App\Modules\Bookings\Contracts\DepartureManifests::class)->countsFor([$departure->ulid]))->toBe([$departure->ulid => 2]);
});

it('assigns guide and vehicle avoiding schedule clashes and small vehicles', function (): void {
    $morning = tourDeparture('2026-11-10', '09:00');
    $overlapping = tourDeparture('2026-11-10', '11:00');
    $afternoon = tourDeparture('2026-11-10', '14:00');
    passengersOn($morning);
    $guide = Guide::query()->create(['name' => 'Camila Guía', 'phone' => '3001112233', 'is_active' => true]);
    $van = Vehicle::query()->create(['plate' => 'ABC123', 'description' => 'Van', 'capacity' => 12, 'is_active' => true]);
    $car = Vehicle::query()->create(['plate' => 'CAR001', 'description' => 'Auto', 'capacity' => 1, 'is_active' => true]);
    $assign = app(AssignDepartureResourcesAction::class);

    $assignment = $assign->execute(operator(), $morning->ulid, $guide, $van);

    expect($assignment->guide_id)->toBe($guide->id)
        ->and($assignment->ends_at->diffInMinutes($assignment->starts_at, true))->toBe(240.0)
        ->and(fn() => $assign->execute(operator(), $overlapping->ulid, $guide, null))->toThrow(OperationsRuleViolation::class)
        ->and(fn() => $assign->execute(operator(), $overlapping->ulid, null, $van))->toThrow(OperationsRuleViolation::class)
        ->and(fn() => $assign->execute(operator(), $morning->ulid, $guide, $car))->toThrow(OperationsRuleViolation::class, __('operations.errors.vehicle_too_small', ['capacity' => 1, 'passengers' => 2]))
        ->and($assign->execute(operator(), $afternoon->ulid, $guide, $van)->departure_ulid)->toBe($afternoon->ulid)
        ->and($assign->execute(operator(), $morning->ulid, null, null)->guide_id)->toBeNull()
        ->and(DepartureAssignment::query()->count())->toBe(2);

    $guide->update(['is_active' => false]);
    expect(fn() => $assign->execute(operator(), $overlapping->ulid, $guide, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.inactive_resource'));
});

it('creates and edits guides and vehicles with validated forms', function (): void {
    actingAs(operator());

    Livewire::test(GuideForm::class)
        ->set('phone', 'abc')
        ->call('save')
        ->assertHasErrors(['name', 'phone'])
        ->set('name', 'Camila Guía')
        ->set('phone', '+57 300 111 2233')
        ->set('languages', 'Español, Inglés')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('operations.resources'));
    $guide = Guide::query()->sole();
    Livewire::test(GuideForm::class, ['guide' => $guide])->assertSet('name', 'Camila Guía')->set('name', 'Camila R.')->call('save')->assertHasNoErrors();

    Livewire::test(VehicleForm::class)
        ->set('plate', 'a')
        ->set('capacity', '0')
        ->call('save')
        ->assertHasErrors(['plate', 'description', 'capacity'])
        ->set('plate', 'abc-123')
        ->set('description', 'Buseta 19')
        ->set('capacity', '19')
        ->call('save')
        ->assertHasNoErrors();
    $vehicle = Vehicle::query()->sole();
    Livewire::test(VehicleForm::class)->set('plate', 'ABC-123')->set('description', 'Otra')->set('capacity', '4')->call('save')->assertHasErrors('plate');
    Livewire::test(VehicleForm::class, ['vehicle' => $vehicle])->set('capacity', '20')->call('save')->assertHasNoErrors();

    expect($guide->fresh()?->name)->toBe('Camila R.')
        ->and($vehicle->fresh()?->plate)->toBe('ABC-123')
        ->and($vehicle->fresh()?->capacity)->toBe(20);
    Livewire::test(ResourcesIndex::class)->assertSee('Camila R.')->assertSee('ABC-123');
});

it('shows departures, the manifest and assigns from the screen', function (): void {
    $departure = tourDeparture();
    passengersOn($departure);
    Guide::query()->create(['name' => 'Camila Guía', 'phone' => '3001112233', 'is_active' => true]);
    Vehicle::query()->create(['plate' => 'ABC123', 'description' => 'Van', 'capacity' => 12, 'is_active' => true]);
    actingAs(operator());

    Livewire::test(DeparturesBoard::class)
        ->set('from', '2026-11-09')
        ->assertSee($departure->product->name)
        ->assertSee(__('operations.departures.missing_guide'))
        ->set('from', '2026-12-01')
        ->assertSee(__('operations.departures.empty'));

    Livewire::test(DepartureShow::class, ['departure' => $departure->ulid])
        ->assertSee(__('operations.manifest.heading'))
        ->set('guide', Guide::query()->value('ulid'))
        ->set('vehicle', Vehicle::query()->value('ulid'))
        ->call('assign')
        ->assertHasNoErrors()
        ->assertSee(__('operations.departures.assigned'))
        ->set('vehicle', 'no-existe')
        ->call('assign')
        ->assertHasErrors('vehicle');

    actingAs(operator())->get(route('operations.manifest', $departure->ulid))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('reports assignment conflicts on the screen', function (): void {
    $departure = tourDeparture();
    passengersOn($departure);
    Vehicle::query()->create(['plate' => 'CAR001', 'description' => 'Auto', 'capacity' => 1, 'is_active' => true]);
    actingAs(operator());

    Livewire::test(DepartureShow::class, ['departure' => $departure->ulid])
        ->set('vehicle', Vehicle::query()->value('ulid'))
        ->call('assign')
        ->assertHasErrors('assignment');
});

it('restricts operations to users with the operations permission', function (): void {
    $departure = tourDeparture();

    actingAs(agent())->get(route('operations.departures'))->assertForbidden();
    actingAs(agent())->get(route('operations.resources'))->assertForbidden();
    actingAs(agent())->get(route('operations.manifest', $departure->ulid))->assertForbidden();
    actingAs(operator())->get(route('operations.departures.show', 'no-existe'))->assertNotFound();
    actingAs(userWithRole(Role::AgencyOwner))->get(route('operations.departures'))->assertOk();
});

it('reports and resolves incidents and closes the departure once it is over', function (): void {
    $departure = tourDeparture();
    passengersOn($departure);
    $guide = Guide::query()->create(['name' => 'Camila Guía', 'phone' => '3001112233', 'is_active' => true]);
    $actor = operator();
    $close = app(App\Modules\Operations\Actions\CloseDepartureAction::class);

    expect(fn() => $close->execute($actor, $departure->ulid, 0, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.departure_not_finished'));

    $this->travelTo(CarbonImmutable::parse('2026-11-10 20:00:00'));
    expect(fn() => $close->execute($actor, $departure->ulid, 0, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.close_without_guide'));
    app(AssignDepartureResourcesAction::class)->execute($actor, $departure->ulid, $guide, null);

    $incident = app(App\Modules\Operations\Actions\ReportIncidentAction::class)->execute($actor, $departure->ulid, App\Modules\Operations\Enums\IncidentSeverity::High, 'Retraso de 40 minutos por lluvia.');
    expect(fn() => $close->execute($actor, $departure->ulid, 0, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.open_incidents'))
        ->and(fn() => $close->execute($actor, $departure->ulid, 3, null))->toThrow(OperationsRuleViolation::class);

    app(App\Modules\Operations\Actions\ResolveIncidentAction::class)->execute($actor, $incident, 'Se informó a los pasajeros y se extendió el tour.');
    expect(fn() => $close->execute($actor, $departure->ulid, 3, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.invalid_no_shows', ['passengers' => 2]));

    $closure = $close->execute($actor, $departure->ulid, 1, 'Un pasajero no se presentó.');

    expect($closure->attended)->toBe(1)
        ->and($closure->no_shows)->toBe(1)
        ->and(fn() => app(AssignDepartureResourcesAction::class)->execute($actor, $departure->ulid, null, null))->toThrow(OperationsRuleViolation::class, __('operations.errors.departure_closed'))
        ->and(fn() => app(App\Modules\Operations\Actions\ReportIncidentAction::class)->execute($actor, $departure->ulid, App\Modules\Operations\Enums\IncidentSeverity::Low, 'Otra'))->toThrow(OperationsRuleViolation::class);
});

it('manages incidents and the closure from the departure screen', function (): void {
    $departure = tourDeparture();
    passengersOn($departure);
    Guide::query()->create(['name' => 'Camila Guía', 'phone' => '3001112233', 'is_active' => true]);
    actingAs(operator());

    $screen = Livewire::test(DepartureShow::class, ['departure' => $departure->ulid])
        ->set('description', '')
        ->call('reportIncident')
        ->assertHasErrors('description')
        ->set('description', 'Un pasajero se sintió mal.')
        ->call('reportIncident')
        ->assertHasNoErrors()
        ->assertSee('Un pasajero se sintió mal.');
    $incident = App\Modules\Operations\Models\DepartureIncident::query()->sole();

    $screen->call('resolveIncident', $incident->ulid)
        ->assertHasErrors("resolutions.{$incident->ulid}")
        ->set("resolutions.{$incident->ulid}", 'Atendido por el guía.')
        ->call('resolveIncident', $incident->ulid)
        ->assertHasNoErrors()
        ->assertSee(__('operations.incidents.resolved'))
        ->call('close')
        ->assertHasErrors('closure');

    $this->travelTo(CarbonImmutable::parse('2026-11-10 20:00:00'));
    $screen->set('guide', Guide::query()->value('ulid'))
        ->call('assign')
        ->set('noShows', '0')
        ->call('close')
        ->assertHasNoErrors()
        ->assertSee(__('operations.closure.summary', ['date' => App\Modules\Operations\Models\DepartureClosure::query()->sole()->closed_at->timezone(config('travel.agency.timezone'))->isoFormat('lll'), 'attended' => 2, 'no_shows' => 0]));
});
