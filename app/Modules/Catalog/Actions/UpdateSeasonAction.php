<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Models\CatalogSeason;
use App\Modules\Shared\Enums\PassengerType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Corrige una temporada (nombre, fechas y netos). Lo ya cotizado o reservado conserva su precio congelado;
 * el cambio aplica a lo que se cotice desde ahora. Queda auditado por el modelo.
 */
final class UpdateSeasonAction
{
    /**
     * @param  array<value-of<PassengerType>, int>  $netAmountsMinor  costo neto por pasajero en unidades menores; un tipo ausente se elimina
     */
    public function execute(CatalogSeason $season, string $name, CarbonImmutable $startsOn, CarbonImmutable $endsOn, array $netAmountsMinor): CatalogSeason
    {
        return DB::transaction(static function () use ($season, $name, $startsOn, $endsOn, $netAmountsMinor): CatalogSeason {
            CatalogProduct::query()->whereKey($season->product_id)->lockForUpdate()->first();

            $overlaps = CatalogSeason::query()
                ->where('product_id', $season->product_id)
                ->whereKeyNot($season->id)
                ->where('starts_on', '<=', $endsOn->toDateString())
                ->where('ends_on', '>=', $startsOn->toDateString())
                ->exists();

            if ($overlaps) {
                throw CatalogRuleViolation::overlappingSeason();
            }

            $season->update(['name' => $name, 'starts_on' => $startsOn->toDateString(), 'ends_on' => $endsOn->toDateString()]);

            foreach (PassengerType::cases() as $type) {
                $rate = $season->rates()->where('passenger_type', $type->value)->first();
                $amount = $netAmountsMinor[$type->value] ?? null;

                if ($amount === null) {
                    $rate?->delete();
                } elseif ($rate === null) {
                    $season->rates()->create(['passenger_type' => $type, 'net_amount_minor' => $amount]);
                } else {
                    $rate->update(['net_amount_minor' => $amount]);
                }
            }

            return $season->refresh();
        });
    }
}
