<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Data\NetPriceQuote;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Models\CatalogRate;
use App\Modules\Catalog\Models\CatalogSeason;
use App\Modules\Shared\Enums\PassengerType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

final class EloquentCatalogRates implements CatalogRates
{
    public function netPriceFor(string $productUlid, CarbonImmutable $serviceDate, array $passengerAgesAtService): NetPriceQuote
    {
        $product = CatalogProduct::query()->where('ulid', $productUlid)->firstOrFail();
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
}
