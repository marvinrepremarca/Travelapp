<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use Carbon\CarbonImmutable;

/** Programa una salida (fecha y hora locales del destino) con su cupo; nace abierta a la venta. */
final class AddDepartureAction
{
    public function execute(CatalogProduct $product, CarbonImmutable $serviceDate, string $startsAt, int $capacity): CatalogDeparture
    {
        $departure = new CatalogDeparture([
            'product_id' => $product->id,
            'service_date' => $serviceDate->toDateString(),
            'starts_at' => $startsAt,
            'capacity' => $capacity,
        ]);
        $departure->status = DepartureStatus::Open;
        $departure->reserved_seats = 0;
        $departure->save();

        return $departure;
    }
}
