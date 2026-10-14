<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogDeparture;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Corrige una salida. El cupo nunca baja de los cupos ya vendidos, y la fecha u hora solo cambian
 * mientras no haya pasajeros: si ya hay ventas, se cierra la salida y se programa otra.
 */
final class UpdateDepartureAction
{
    public function execute(CatalogDeparture $departure, CarbonImmutable $serviceDate, string $startsAt, int $capacity): CatalogDeparture
    {
        return DB::transaction(static function () use ($departure, $serviceDate, $startsAt, $capacity): CatalogDeparture {
            /** @var CatalogDeparture $locked */
            $locked = CatalogDeparture::query()->whereKey($departure->id)->lockForUpdate()->firstOrFail();

            if ($capacity < $locked->reserved_seats) {
                throw CatalogRuleViolation::capacityBelowReserved($locked->reserved_seats);
            }

            $moved = $locked->service_date->toDateString() !== $serviceDate->toDateString() || substr($locked->starts_at, 0, 5) !== substr($startsAt, 0, 5);
            if ($moved && $locked->reserved_seats > 0) {
                throw CatalogRuleViolation::departureHasPassengers();
            }

            $locked->update(['service_date' => $serviceDate->toDateString(), 'starts_at' => $startsAt, 'capacity' => $capacity]);

            return $locked;
        });
    }
}
