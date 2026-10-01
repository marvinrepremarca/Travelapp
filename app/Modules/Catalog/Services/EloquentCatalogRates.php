<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Data\NetPriceQuote;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogPackageComponent;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Models\CatalogRate;
use App\Modules\Catalog\Models\CatalogSeason;
use App\Modules\Shared\Enums\PassengerType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentCatalogRates implements CatalogRates
{
    private const SEASON_NAMES_SEPARATOR = ' + ';

    public function netPriceFor(string $productUlid, CarbonImmutable $serviceDate, array $passengerAgesAtService): NetPriceQuote
    {
        $product = CatalogProduct::query()->where('ulid', $productUlid)->firstOrFail();

        return $product->isPackage()
            ? $this->packagePrice($product, $serviceDate, $passengerAgesAtService)
            : $this->productPrice($product, $serviceDate, $passengerAgesAtService);
    }

    public function fromPriceFor(string $productUlid, CarbonImmutable $today): ?Money
    {
        return $this->fromPriceOf(CatalogProduct::query()->where('ulid', $productUlid)->firstOrFail(), $today);
    }

    /** Variante para pantallas del módulo que ya tienen el producto cargado (evita releerlo). */
    public function fromPriceOf(CatalogProduct $product, CarbonImmutable $today): ?Money
    {
        if (! $product->isPackage()) {
            return $this->cheapestAdultRate([$product->id], $today)[$product->id] ?? null;
        }

        $componentIds = $product->components()->pluck('component_id')->map(static fn(mixed $id): int => (int) $id)->all();
        if ($componentIds === []) {
            return null;
        }

        $cheapest = $this->cheapestAdultRate($componentIds, $today);
        $total = Money::zero($product->currency);
        foreach ($componentIds as $componentId) {
            // Un componente sin tarifa vigente o futura deja al paquete sin precio "desde".
            if (! isset($cheapest[$componentId])) {
                return null;
            }
            $total = $total->plus($cheapest[$componentId]);
        }

        return $total;
    }

    /** @param list<int> $passengerAgesAtService */
    private function productPrice(CatalogProduct $product, CarbonImmutable $serviceDate, array $passengerAgesAtService): NetPriceQuote
    {
        if (! $product->is_active) {
            throw CatalogRuleViolation::productInactive();
        }

        $season = CatalogSeason::query()
            ->with('rates')
            ->where('product_id', $product->id)
            ->where('starts_on', '<=', $serviceDate->toDateString())
            ->where('ends_on', '>=', $serviceDate->toDateString())
            ->first() ?? throw CatalogRuleViolation::noSeasonForDate($serviceDate->toDateString());

        $counts = array_count_values(array_map(static fn(int $age): string => PassengerType::forAge($age)->value, $passengerAgesAtService));
        $lines = [];
        $total = Money::zero($product->currency);

        foreach ($counts as $type => $count) {
            $rate = $season->rates->first(static fn(CatalogRate $rate): bool => $rate->passenger_type->value === $type)
                ?? throw CatalogRuleViolation::noRateForPassengerType(PassengerType::from($type)->label());
            $unit = Money::ofMinor($rate->net_amount_minor, $product->currency);
            $lines[$type] = ['count' => $count, 'unit' => $unit];
            $total = $total->plus($unit->multipliedBy($count));
        }

        return new NetPriceQuote($season->name, $lines, $total);
    }

    /**
     * Suma cada componente en su fecha real (inicio + día del itinerario). Las edades se toman al inicio del paquete.
     *
     * @param  list<int>  $passengerAgesAtService
     */
    private function packagePrice(CatalogProduct $package, CarbonImmutable $startDate, array $passengerAgesAtService): NetPriceQuote
    {
        if (! $package->is_active) {
            throw CatalogRuleViolation::productInactive();
        }

        $components = $package->components()->with('component')->orderBy('day_offset')->get();
        if ($components->isEmpty()) {
            throw CatalogRuleViolation::emptyPackage();
        }

        $lines = [];
        $seasonNames = [];
        $total = Money::zero($package->currency);

        foreach ($components as $component) {
            $quote = $this->componentPrice($component, $startDate, $passengerAgesAtService);
            $seasonNames[] = $quote->seasonName;
            $total = $total->plus($quote->total);
            foreach ($quote->lines as $type => $line) {
                $lines[$type] = ['count' => $line['count'], 'unit' => isset($lines[$type]) ? $lines[$type]['unit']->plus($line['unit']) : $line['unit']];
            }
        }

        return new NetPriceQuote(implode(self::SEASON_NAMES_SEPARATOR, array_unique($seasonNames)), $lines, $total);
    }

    /** @param list<int> $passengerAgesAtService */
    private function componentPrice(CatalogPackageComponent $component, CarbonImmutable $startDate, array $passengerAgesAtService): NetPriceQuote
    {
        return $this->productPrice($component->component, $startDate->addDays($component->day_offset), $passengerAgesAtService);
    }

    /**
     * Menor neto de adulto entre las temporadas vigentes o futuras de cada producto.
     *
     * @param  array<int, int>  $productIds
     * @return array<int, Money>
     */
    private function cheapestAdultRate(array $productIds, CarbonImmutable $today): array
    {
        $rows = CatalogRate::query()
            ->join('catalog_seasons', 'catalog_seasons.id', '=', 'catalog_rates.season_id')
            ->join('catalog_products', 'catalog_products.id', '=', 'catalog_seasons.product_id')
            ->whereIn('catalog_seasons.product_id', $productIds)
            ->where('catalog_seasons.ends_on', '>=', $today->toDateString())
            ->where('catalog_rates.passenger_type', PassengerType::Adult->value)
            ->where(static fn(Builder $query) => $query->where('catalog_products.is_active', true))
            ->groupBy('catalog_seasons.product_id', 'catalog_products.currency')
            ->selectRaw('catalog_seasons.product_id as product_id, catalog_products.currency as currency, min(catalog_rates.net_amount_minor) as amount_minor')
            ->toBase()
            ->get();

        $cheapest = [];
        foreach ($rows as $row) {
            $cheapest[(int) $row->product_id] = Money::ofMinor((int) $row->amount_minor, (string) $row->currency);
        }

        return $cheapest;
    }
}
