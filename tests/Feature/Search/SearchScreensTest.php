<?php

declare(strict_types=1);

use App\Modules\Integrations\Adapters\Fake\FakeFlights;
use App\Modules\Integrations\Adapters\Fake\FakeHotels;
use App\Modules\Pricing\Actions\RecordExchangeRateAction;
use App\Modules\Pricing\Enums\ExchangeRateSource;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Search\Livewire\FlightSearch;
use App\Modules\Search\Livewire\HotelSearch;
use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

it('requires login for the search screens', function (): void {
    get(route('search.flights'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('search.hotels'))->assertOk();
});

it('searches flights and shows sale prices from pricing rules', function (): void {
    app(RecordExchangeRateAction::class)->execute('USD', 'COP', BigDecimal::of('4000'), ExchangeRateSource::Manual, CarbonImmutable::parse('2026-10-01'));
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'bog')
        ->set('criteria.destination', 'ctg')
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '35, 33')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSee('BOG 06:30')
        ->assertSee(__('search.provider', ['provider' => FakeFlights::KEY]))
        ->assertDontSee(__('search.no_rate'));
});

it('warns when the sale price cannot be calculated without exchange rate', function (): void {
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'BOG')
        ->set('criteria.destination', 'CTG')
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertSee(__('search.no_rate'));
});

it('shows partial and empty results', function (): void {
    config(['travel.search.flight_providers' => ['fake']]);
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'BOG')
        ->set('criteria.destination', FakeFlights::FAILING_DESTINATION)
        ->set('criteria.departure_date', '2026-11-10')
        ->set('criteria.ages', '35')
        ->call('search')
        ->assertSee(__('search.partial', ['providers' => FakeFlights::KEY]))
        ->assertSee(__('search.empty_title'));
});

it('validates flight searches', function (): void {
    actingAs(agent());

    Livewire::test(FlightSearch::class)
        ->set('criteria.origin', 'BO')
        ->set('criteria.destination', 'BO')
        ->set('criteria.departure_date', '2026-09-01')
        ->set('criteria.return_date', '2026-08-01')
        ->set('criteria.ages', '1,2,3,4,5,6,7,8,9,10')
        ->set('criteria.cabin', 'cargo')
        ->call('search')
        ->assertHasErrors(['criteria.origin', 'criteria.destination', 'criteria.departure_date', 'criteria.return_date', 'criteria.ages', 'criteria.cabin']);
});

it('searches hotels with nights, board and sale price', function (): void {
    actingAs(agent());

    Livewire::test(HotelSearch::class)
        ->set('criteria.city', 'Cartagena')
        ->set('criteria.country', 'co')
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2026-11-13')
        ->set('criteria.ages', '35, 33')
        ->call('search')
        ->assertHasNoErrors()
        ->assertSee('Resort Caribe de Prueba Cartagena')
        ->assertSee(trans_choice('search.hotels.nights', 3, ['count' => 3]))
        ->assertSee(App\Modules\Search\Enums\BoardType::Breakfast->label());
});

it('validates hotel searches and limits the stay length', function (): void {
    actingAs(agent());

    Livewire::test(HotelSearch::class)
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2027-01-30')
        ->set('criteria.ages', 'dos')
        ->call('search')
        ->assertHasErrors(['criteria.city', 'criteria.country', 'criteria.check_out', 'criteria.ages']);

    Livewire::test(HotelSearch::class)
        ->set('criteria.city', FakeHotels::SOLD_OUT_CITY)
        ->set('criteria.country', 'CO')
        ->set('criteria.check_in', '2026-11-10')
        ->set('criteria.check_out', '2026-11-11')
        ->set('criteria.ages', '30')
        ->call('search')
        ->assertSee(__('search.empty_title'));
});
