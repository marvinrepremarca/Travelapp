<?php

declare(strict_types=1);

namespace App\Modules\Search\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Contracts\SupplierOfferIntake;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Search\Data\FlightOffer;
use App\Modules\Search\Data\FlightSearchCriteria;
use App\Modules\Search\Data\SearchResult;
use App\Modules\Search\Enums\CabinClass;
use App\Modules\Search\Services\OfferPricing;
use App\Modules\Search\Services\SearchAggregator;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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

    /** Cotización en borrador a la que se agregan las ofertas. */
    public string $targetQuote = '';

    public function search(): void
    {
        $this->validate($this->rules(), attributes: $this->attributes());
        $this->searched = true;
    }

    public function addToQuote(string $offerKey, SearchAggregator $aggregator, SupplierOfferIntake $intake): void
    {
        $this->validate($this->rules(), attributes: $this->attributes());
        $criteria = $this->toCriteria();
        $offer = collect($aggregator->flights($criteria)->offers)->first(static fn(FlightOffer|\App\Modules\Search\Data\HotelOffer $candidate): bool => $candidate->providerKey . $candidate->offerId === $offerKey);
        if (! $offer instanceof FlightOffer || $this->targetQuote === '') {
            $this->addError('targetQuote', __('search.quote_required'));

            return;
        }

        try {
            $intake->add($this->targetQuote, $this->actor(), new QuoteItemData(
                kind: QuoteItemKind::Manual,
                serviceDate: $criteria->departureDate,
                passengerAges: $criteria->passengerAges,
                productType: ProductType::Flight,
                description: $this->describe($offer),
                manualNet: $offer->totalNet,
                providerKey: $offer->providerKey,
                providerOfferId: $offer->offerId,
            ));
        } catch (BusinessRuleException $violation) {
            $this->addError('targetQuote', $violation->getMessage());

            return;
        }

        session()->flash('status', __('search.added_to_quote'));
    }

    public function render(SearchAggregator $aggregator, OfferPricing $pricing, MoneyPresenter $presenter): View
    {
        $result = $this->searched ? $aggregator->flights($this->toCriteria()) : null;

        return view('search::livewire.flight-search', [
            'result' => $result,
            'prices' => $result instanceof SearchResult ? $this->prices($result, $pricing) : [],
            'cabins' => CabinClass::cases(),
            'drafts' => app(SupplierOfferIntake::class)->draftsFor($this->actor()),
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

    private function describe(FlightOffer $offer): string
    {
        $first = $offer->outbound[0] ?? null;

        return __($offer->inbound === [] ? 'search.flights.description_one_way' : 'search.flights.description_round_trip', [
            'origin' => $first->origin ?? mb_strtoupper($this->criteria['origin']),
            'destination' => $first->destination ?? mb_strtoupper($this->criteria['destination']),
            'flight' => $first === null ? '' : $first->carrierName . ' ' . $first->flightNumber,
        ]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
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
