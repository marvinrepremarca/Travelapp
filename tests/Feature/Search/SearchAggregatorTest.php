<?php

declare(strict_types=1);

use App\Modules\Integrations\Adapters\Fake\FakeFlights;
use App\Modules\Integrations\Adapters\Fake\FakeHotels;
use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Data\FlightOffer;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Enums\BoardType;
use App\Modules\Search\Exceptions\ProviderUnavailable;
use App\Modules\Search\Services\SearchAggregator;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Segundo proveedor de prueba para demostrar el cambio y la combinación de proveedores. */
final class CheapAirline implements FlightProvider
{
    public static int $calls = 0;

    public static bool $failing = false;

    public function key(): string
    {
        return 'cheap';
    }

    public function search(FlightSearchCriteria $criteria): array
    {
        self::$calls++;
        if (self::$failing) {
            throw ProviderUnavailable::for('cheap');
        }

        return [new FlightOffer('cheap', 'c-1', Money::of('99', 'USD'), [], [], false)];
    }

    public function reprice(string $offerId): \Brick\Money\Money
    {
        return Money::of('99', 'USD');
    }

    public function book(App\Modules\Search\Data\ProviderBookingRequest $request): string
    {
        return 'CHEAP-1';
    }

    public function cancel(string $bookingReference): void {}
}

beforeEach(function (): void {
    CheapAirline::$calls = 0;
    CheapAirline::$failing = false;
    app()->tag([CheapAirline::class], FlightProvider::TAG);
});

function flightCriteria(string $destination = 'CTG'): FlightSearchCriteria
{
    return new FlightSearchCriteria('BOG', $destination, CarbonImmutable::parse('2026-11-10'), [35, 33], CarbonImmutable::parse('2026-11-15'));
}

it('returns deterministic fake flights priced for every passenger', function (): void {
    $offers = app(FakeFlights::class)->search(flightCriteria());
    $again = app(FakeFlights::class)->search(flightCriteria());

    expect($offers)->toHaveCount(3)
        ->and($offers[0]->offerId)->toBe($again[0]->offerId)
        ->and($offers[0]->outbound[0]->origin)->toBe('BOG')
        ->and($offers[0]->inbound[0]->destination)->toBe('BOG')
        ->and($offers[0]->totalNet->getMinorAmount()->toInt() % 2)->toBe(0)
        ->and($offers[0]->stops())->toBe(0);
});

it('simulates sold out and failing fake providers', function (): void {
    expect(app(FakeFlights::class)->search(flightCriteria(FakeFlights::SOLD_OUT_DESTINATION)))->toBe([])
        ->and(fn() => app(FakeFlights::class)->search(flightCriteria(FakeFlights::FAILING_DESTINATION)))->toThrow(ProviderUnavailable::class);
});

it('returns fake hotels priced per night with board and cancellation', function (): void {
    $criteria = new HotelSearchCriteria('Cartagena', 'co', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-13'), [35, 33]);
    $offers = app(FakeHotels::class)->search($criteria);

    expect($criteria->nights())->toBe(3)
        ->and($offers)->toHaveCount(3)
        ->and((string) $offers[0]->totalNet->getAmount())->toBe('840000.00')
        ->and($offers[0]->refundable)->toBeFalse()
        ->and($offers[1]->boardType)->toBe(BoardType::Breakfast)
        ->and($offers[1]->freeCancellationUntil?->toDateString())->toBe('2026-11-07')
        ->and(app(FakeHotels::class)->search(new HotelSearchCriteria(FakeHotels::SOLD_OUT_CITY, 'CO', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-11'), [30])))->toBe([])
        ->and(fn() => app(FakeHotels::class)->search(new HotelSearchCriteria(FakeHotels::FAILING_CITY, 'CO', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-11'), [30])))->toThrow(ProviderUnavailable::class);
});

it('uses only the providers enabled in configuration, in any combination', function (array $enabled, array $expectedProviders): void {
    config(['travel.search.flight_providers' => $enabled]);

    $result = app(SearchAggregator::class)->flights(flightCriteria());

    expect(array_values(array_unique(array_map(static fn(FlightOffer $offer): string => $offer->providerKey, $result->offers))))->toBe($expectedProviders);
})->with([
    'only fake' => [['fake'], ['fake']],
    'only the other provider' => [['cheap'], ['cheap']],
    'both, cheapest first' => [['fake', 'cheap'], ['cheap', 'fake']],
    'unknown keys are ignored' => [['amadeus', 'fake'], ['fake']],
    'nothing enabled' => [[], []],
]);

it('returns partial results when a provider fails', function (): void {
    config(['travel.search.flight_providers' => ['fake', 'cheap']]);
    CheapAirline::$failing = true;

    $result = app(SearchAggregator::class)->flights(flightCriteria());

    expect($result->offers)->toHaveCount(3)
        ->and($result->unavailableProviders)->toBe(['cheap']);
});

it('pauses a failing provider after the threshold and retries after the cooldown', function (): void {
    config(['travel.search.flight_providers' => ['cheap'], 'travel.search.breaker_failure_threshold' => 2, 'travel.search.breaker_cooldown_seconds' => 60]);
    CheapAirline::$failing = true;
    $aggregator = app(SearchAggregator::class);

    $aggregator->flights(flightCriteria());
    $aggregator->flights(flightCriteria());
    $paused = $aggregator->flights(flightCriteria());

    expect(CheapAirline::$calls)->toBe(2)
        ->and($paused->unavailableProviders)->toBe(['cheap']);

    CheapAirline::$failing = false;
    $this->travel(61)->seconds();
    expect($aggregator->flights(flightCriteria())->offers)->toHaveCount(1)
        ->and(CheapAirline::$calls)->toBe(3);
});

it('caches complete searches but not partial ones', function (): void {
    config(['travel.search.flight_providers' => ['cheap']]);
    $aggregator = app(SearchAggregator::class);

    $aggregator->flights(flightCriteria());
    $aggregator->flights(flightCriteria());
    expect(CheapAirline::$calls)->toBe(1);

    CheapAirline::$failing = true;
    $aggregator->flights(flightCriteria('MDE'));
    $aggregator->flights(flightCriteria('MDE'));
    expect(CheapAirline::$calls)->toBe(3);
});

it('searches hotels through the configured providers', function (): void {
    config(['travel.search.hotel_providers' => ['fake']]);

    $result = app(SearchAggregator::class)->hotels(new HotelSearchCriteria('Cartagena', 'CO', CarbonImmutable::parse('2026-11-10'), CarbonImmutable::parse('2026-11-12'), [30]));

    expect($result->offers)->toHaveCount(3)
        ->and($result->offers[0]->totalNet->isLessThan($result->offers[2]->totalNet))->toBeTrue();
});

it('labels search enums', function (): void {
    foreach ([...App\Modules\Search\Enums\CabinClass::cases(), ...BoardType::cases()] as $case) {
        expect($case->label())->not->toStartWith('search.');
    }
});
