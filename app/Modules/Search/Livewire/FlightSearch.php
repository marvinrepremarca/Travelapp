<?php

declare(strict_types=1);

namespace App\Modules\Search\Livewire;

use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\SearchResult;
use App\Modules\Search\Enums\CabinClass;
use App\Modules\Search\Services\OfferPricing;
use App\Modules\Search\Services\SearchAggregator;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Búsqueda de vuelos en todos los proveedores activos, con precio de venta calculado por Pricing. */
#[Layout('components.layouts.backoffice')]
final class FlightSearch extends Component
{
    private const AGES_SEPARATOR = ',';

    /** @var array<string, string> */
    public array $criteria = ['origin' => '', 'destination' => '', 'departure_date' => '', 'return_date' => '', 'ages' => '', 'cabin' => 'economy'];

    public bool $searched = false;

    public function search(): void
    {
        $this->validate($this->rules(), attributes: $this->attributes());
        $this->searched = true;
    }

    public function render(SearchAggregator $aggregator, OfferPricing $pricing, MoneyPresenter $presenter): View
    {
        $result = $this->searched ? $aggregator->flights($this->toCriteria()) : null;

        return view('search::livewire.flight-search', [
            'result' => $result,
            'prices' => $result instanceof SearchResult ? $this->prices($result, $pricing) : [],
            'cabins' => CabinClass::cases(),
            'presenter' => $presenter,
        ])->title(__('search.flights.title'))
            ->layoutData(['heading' => __('search.flights.title')]);
    }

    /**
     * @return array<string, \Brick\Money\Money|null>
     */
    private function prices(SearchResult $result, OfferPricing $pricing): array
    {
        $criteria = $this->toCriteria();
        $prices = [];
        foreach ($result->offers as $offer) {
            $prices[$offer->providerKey . $offer->offerId] = $pricing->sale($offer->totalNet, ProductType::Flight, $criteria->departureDate, count($criteria->passengerAges), 0);
        }

        return $prices;
    }

    private function toCriteria(): FlightSearchCriteria
    {
        return new FlightSearchCriteria(
            origin: mb_strtoupper($this->criteria['origin']),
            destination: mb_strtoupper($this->criteria['destination']),
            departureDate: CarbonImmutable::parse($this->criteria['departure_date']),
            passengerAges: array_map(static fn(string $age): int => (int) trim($age), explode(self::AGES_SEPARATOR, $this->criteria['ages'])),
            returnDate: $this->criteria['return_date'] === '' ? null : CarbonImmutable::parse($this->criteria['return_date']),
            cabin: CabinClass::from($this->criteria['cabin']),
        );
    }

    /** @return array<string, list<mixed>> */
    private function rules(): array
    {
        $maxPassengers = config()->integer('travel.search.max_passengers');

        return [
            'criteria.origin' => ['required', 'string', 'size:3', 'alpha'],
            'criteria.destination' => ['required', 'string', 'size:3', 'alpha', 'different:criteria.origin'],
            'criteria.departure_date' => ['required', 'date', 'after_or_equal:today'],
            'criteria.return_date' => ['nullable', 'date', 'after_or_equal:criteria.departure_date'],
            'criteria.ages' => ['required', 'string', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3}){0,' . ($maxPassengers - 1) . '}\s*$/'],
            'criteria.cabin' => ['required', Rule::enum(CabinClass::class)],
        ];
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('search.flights.fields');

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["criteria.{$field}" => $label])->all();
    }
}
