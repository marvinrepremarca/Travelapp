<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Models\CatalogSeason;
use App\Modules\Shared\Enums\PassengerType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Agrega una temporada con su costo neto por tipo de pasajero. Las temporadas de un producto no se cruzan. */
final class AddSeasonAction
{
    /**
     * @param  array<value-of<PassengerType>, int>  $netAmountsMinor  costo neto por pasajero en unidades menores
     */
    public function execute(CatalogProduct $product, string $name, CarbonImmutable $startsOn, CarbonImmutable $endsOn, array $netAmountsMinor): CatalogSeason
    {
        return DB::transaction(static function () use ($product, $name, $startsOn, $endsOn, $netAmountsMinor): CatalogSeason {
            // Bloquea el producto para que dos temporadas simultáneas no se crucen.
            CatalogProduct::query()->whereKey($product->id)->lockForUpdate()->first();

            $overlaps = CatalogSeason::query()
                ->where('product_id', $product->id)
                ->where('starts_on', '<=', $endsOn->toDateString())
                ->where('ends_on', '>=', $startsOn->toDateString())
                ->exists();

            if ($overlaps) {
                throw CatalogRuleViolation::overlappingSeason();
            }

            $season = CatalogSeason::query()->create([
                'product_id' => $product->id,
                'name' => $name,
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $endsOn->toDateString(),
            ]);

            foreach ($netAmountsMinor as $type => $amountMinor) {
                $season->rates()->create(['passenger_type' => PassengerType::from($type), 'net_amount_minor' => $amountMinor]);
            }

            return $season;
        });
    }
}
