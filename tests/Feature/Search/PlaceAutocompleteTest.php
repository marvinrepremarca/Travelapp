<?php

declare(strict_types=1);

use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Search\Database\Seeders\AirportsSeeder;
use App\Modules\Search\Livewire\FlightSearch;
use App\Modules\Search\Livewire\HotelSearch;
use App\Modules\Search\Models\Airport;
use App\Modules\Search\Services\PlaceDirectory;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    $this->seed(AirportsSeeder::class);
});

it('suggests airports by natural name without accents', function (string $typed, string $code): void {
    $suggestions = app(PlaceDirectory::class)->airports($typed);

    expect($suggestions[0]->value)->toBe($code);
})->with([
    'city without accent' => ['bogota', 'BOG'],
    'partial city' => ['cartag', 'CTG'],
    'iata code first' => ['mde', 'MDE'],
    'airport name' => ['el dorado', 'BOG'],
    'country name' => ['peru', 'LIM'],
]);

it('suggests nothing below the minimum length', function (): void {
    expect(app(PlaceDirectory::class)->airports('b'))->toBe([])
        ->and(app(PlaceDirectory::class)->countries('c'))->toBe([])
        ->and(app(PlaceDirectory::class)->cities(' '))->toBe([]);
});

it('translates what was typed into codes only when it is not ambiguous', function (): void {
    $places = app(PlaceDirectory::class);

    expect($places->airportCode('mad'))->toBe('MAD')
        ->and($places->airportCode('Cartagena (CTG)'))->toBe('CTG')
        ->and($places->airportCode('cartagena'))->toBe('CTG')
        ->and($places->airportCode('nueva york'))->toBeNull()
        ->and($places->countryCode('españa'))->toBe('ES')
        ->and($places->countryCode('co'))->toBe('CO')
        ->and($places->countryCode('Estados Unidos'))->toBe('US')
        ->and($places->countryCode('zzzz'))->toBeNull()
        ->and($places->countryName('CO'))->toBe('Colombia');
});

it('lists each city once with its country', function (): void {
    $labels = array_map(static fn(\App\Modules\Search\Data\PlaceSuggestion $suggestion): string => $suggestion->label, app(PlaceDirectory::class)->cities('nueva york'));

    expect($labels)->toBe(['Nueva York, Estados Unidos']);
});

it('keeps the seeder idempotent', function (): void {
    $this->seed(AirportsSeeder::class);

    expect(Airport::query()->where('iata_code', 'BOG')->count())->toBe(1);
});

it('searches flights choosing places by name', function (): void {
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('lookup.origin', 'bogo')
        ->assertSet('activeLookup', 'origin')
        ->assertSee('Aeropuerto Internacional El Dorado')
        ->call('choose', 'origin', 'BOG')
        ->assertSet('criteria.origin', 'BOG')
        ->assertSet('lookup.origin', 'Bogotá (BOG)')
        ->set('lookup.destination', 'cartagena')
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSet('criteria.destination', 'CTG');
});

it('asks to choose from the list when the place is ambiguous or unknown', function (): void {
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('lookup.origin', 'nueva york')
        ->set('lookup.destination', 'ciudad inexistente')
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertHasErrors(['criteria.origin', 'criteria.destination'])
        ->assertSee(__('search.places.choose_from_list'))
        ->call('choose', 'origin', 'XXX')
        ->assertSet('criteria.origin', '')
        ->call('choose', 'unknown-field', 'BOG')
        ->set('lookup.unknown', 'x')
        ->assertSet('activeLookup', '');
});

it('fills the hotel country when choosing a city and accepts cities without airport', function (): void {
    actingAs(agent());

    Livewire::test(HotelSearch::class)
        ->set('lookup.city', 'cartag')
        ->assertSee('Cartagena, Colombia')
        ->call('choose', 'city', 'CTG')
        ->assertSet('criteria.city', 'Cartagena')
        ->assertSet('criteria.country', 'CO')
        ->assertSet('lookup.country', 'Colombia')
        ->set('lookup.city', 'Villa de Leyva')
        ->assertSet('criteria.city', 'Villa de Leyva')
        ->set('lookup.country', 'colom')
        ->assertSee('Colombia')
        ->call('choose', 'country', 'ZZ')
        ->assertSet('criteria.country', '')
        ->call('choose', 'country', 'co')
        ->assertSet('criteria.country', 'CO')
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2026-11-12')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertHasNoErrors();
});

it('resolves a typed hotel country', function (): void {
    actingAs(agent());

    Livewire::test(HotelSearch::class)
        ->set('lookup.city', 'Madrid')
        ->set('lookup.country', 'España')
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2026-11-12')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSet('criteria.country', 'ES');
});
