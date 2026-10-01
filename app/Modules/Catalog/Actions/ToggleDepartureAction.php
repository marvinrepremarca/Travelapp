<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Catalog\Models\CatalogDeparture;

/** Cierra o reabre la venta de una salida. Los cupos ya apartados se conservan. */
final class ToggleDepartureAction
{
    public function execute(CatalogDeparture $departure): CatalogDeparture
    {
        $departure->status = $departure->status === DepartureStatus::Open ? DepartureStatus::Closed : DepartureStatus::Open;
        $departure->save();

        return $departure;
    }
}
