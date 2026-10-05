<?php

declare(strict_types=1);

namespace App\Modules\Search\Livewire;

use App\Modules\Search\Data\HotelSearchCriteria;
use App\Modules\Search\Data\SearchResult;
use App\Modules\Search\Services\OfferPricing;
use App\Modules\Search\Services\SearchAggregator;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Búsqueda de hoteles en todos los proveedores activos, con precio de venta calculado por Pricing. */
#[Layout('components.layouts.backoffice')]
final class HotelSearch extends Component
{
    private const AGES_SEPARATOR = ',';

    /** @var array<string, string> */
    public array $criteria = ['city' => '', 'country' => '', 'check_in' => '', 'check_out' => '', 'ages' => ''];

    public bool $searched = false;

    public function search(): void
    {
        $maxGuests = config()->integer('travel.search.max_passengers');
        $this->validate([
            'criteria.city' => ['required', 'string', 'max:100'],
            'criteria.country' => ['required', 'string', 'size:2', 'alpha'],
            'criteria.check_in' => ['required', 'date', 'after_or_equal:today'],
            'criteria.check_out' => ['required', 'date', 'after:criteria.check_in', 'before_or_equal:' . CarbonImmutable::parse($this->criteria['check_in'] ?: 'today')->addDays(config()->integer('travel.search.max_nights'))->toDateString()],
            'criteria.ages' => ['required', 'string', 'regex:/^\s*\d{1,3}(\s*,\s*\d{1,3}){0,' . ($maxGuests - 1) . '}\s*$/'],
        ], attributes: $this->attributes());
        $this->searched = true;
    }

    public function render(SearchAggregator $aggregator, OfferPricing $pricing, MoneyPresenter $presenter): View
    {
        $result = $this->searched ? $aggregator->hotels($this->toCriteria()) : null;

        return view('search::livewire.hotel-search', [
            'result' => $result,
            'prices' => $result instanceof SearchResult ? $this->prices($result, $pricing) : [],
            'nights' => $this->searched ? $this->toCriteria()->nights() : 0,
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
