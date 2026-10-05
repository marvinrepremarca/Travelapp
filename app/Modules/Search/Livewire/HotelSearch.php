<?php

declare(strict_types=1);

namespace App\Modules\Search\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Contracts\SupplierOfferIntake;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Search\Data\HotelOffer;
use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Data\SearchResult;
use App\Modules\Search\Enums\PlaceKind;
use App\Modules\Search\Livewire\Concerns\LooksUpPlaces;
use App\Modules\Search\Services\OfferPricing;
use App\Modules\Search\Services\PlaceDirectory;
use App\Modules\Search\Services\SearchAggregator;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Búsqueda de hoteles en todos los proveedores activos, con precio de venta calculado por Pricing. */
#[Layout('components.layouts.backoffice')]
final class HotelSearch extends Component
{
    use LooksUpPlaces;

    private const AGES_SEPARATOR = ',';

    /** @var array<string, string> */
    public array $criteria = ['city' => '', 'country' => '', 'check_in' => '', 'check_out' => '', 'ages' => ''];

    public bool $searched = false;

    public string $targetQuote = '';

    public function addToQuote(string $offerKey, SearchAggregator $aggregator, SupplierOfferIntake $intake): void
    {
        $this->search(app(PlaceDirectory::class));
        $criteria = $this->toCriteria();
        $offer = collect($aggregator->hotels($criteria)->offers)->first(static fn(\App\Modules\Search\Data\FlightOffer|HotelOffer $candidate): bool => $candidate->providerKey . $candidate->offerId === $offerKey);
        if (! $offer instanceof HotelOffer || $this->targetQuote === '') {
            $this->addError('targetQuote', __('search.quote_required'));

            return;
        }

        try {
            $intake->add($this->targetQuote, $this->actor(), new QuoteItemData(
                kind: QuoteItemKind::Manual,
                serviceDate: $criteria->checkIn,
                passengerAges: $criteria->guestAges,
                nights: $criteria->nights(),
                productType: ProductType::Hotel,
                description: $offer->hotelName . ' · ' . $offer->roomName . ' · ' . $offer->boardType->label(),
                manualNet: $offer->totalNet,
                destinationCountry: $criteria->countryCode,
                providerKey: $offer->providerKey,
                providerOfferId: $offer->offerId,
            ));
        } catch (BusinessRuleException $violation) {
            $this->addError('targetQuote', $violation->getMessage());

            return;
        }

        session()->flash('status', __('search.added_to_quote'));
    }

    public function search(PlaceDirectory $places): void
    {
        $this->resolvePlaces($places);
        $maxGuests = config()->integer('travel.search.max_passengers');
        $this->validate([
            'criteria.city' => ['required', 'string', 'max:100'],
            'criteria.country' => ['required', 'string', 'size:2', 'alpha'],
            'criteria.check_in' => ['required', 'date', 'after_or_equal:today'],
            'criteria.check_out' => ['required', 'date', 'after:criteria.check_in', 'before_or_equal:' . CarbonImmutable::parse($this->criteria['check_in'] ?: 'today')->addDays(config()->integer('travel.search.max_nights'))->toDateString()],
            'criteria.ages' => ['required', 'string', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3}){0,' . ($maxGuests - 1) . '}\s*$/'],
        ], $this->placeMessages(), $this->attributes());
        $this->searched = true;
    }

    public function render(SearchAggregator $aggregator, OfferPricing $pricing, MoneyPresenter $presenter, PlaceDirectory $places): View
    {
        $result = $this->searched ? $aggregator->hotels($this->toCriteria()) : null;

        return view('search::livewire.hotel-search', [
            'suggestions' => $this->placeSuggestions($places),
            'result' => $result,
            'prices' => $result instanceof SearchResult ? $this->prices($result, $pricing) : [],
            'nights' => $this->searched ? $this->toCriteria()->nights() : 0,
            'drafts' => app(SupplierOfferIntake::class)->draftsFor($this->actor()),
            'presenter' => $presenter,
        ])->title(__('search.hotels.title'))
            ->layoutData(['heading' => __('search.hotels.title')]);
    }

    /**
     * @return array<string, \Brick\Money\Money|null>
     */
    private function prices(SearchResult $result, OfferPricing $pricing): array
    {
        $criteria = $this->toCriteria();
        $prices = [];
        foreach ($result->offers as $offer) {
            $prices[$offer->providerKey . $offer->offerId] = $pricing->sale($offer->totalNet, ProductType::Hotel, $criteria->checkIn, count($criteria->guestAges), $criteria->nights(), $criteria->countryCode);
        }

        return $prices;
    }

    protected function placeFields(): array
    {
        return ['city' => PlaceKind::City, 'country' => PlaceKind::Country];
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }

    private function toCriteria(): HotelSearchCriteria
    {
        return new HotelSearchCriteria(
            city: trim($this->criteria['city']),
            countryCode: mb_strtoupper($this->criteria['country']),
            checkIn: CarbonImmutable::parse($this->criteria['check_in']),
            checkOut: CarbonImmutable::parse($this->criteria['check_out']),
            guestAges: array_map(static fn(string $age): int => (int) trim($age), explode(self::AGES_SEPARATOR, $this->criteria['ages'])),
        );
    }

    /** @return array<string, string> */
    private function attributes(): array
    {
        /** @var array<string, string> $labels */
        $labels = trans('search.hotels.fields');

        return collect($labels)->mapWithKeys(static fn(string $label, string $field): array => ["criteria.{$field}" => $label])->all();
    }
}
