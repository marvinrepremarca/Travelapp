<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;

/** Producto de la tienda con su precio neto "desde" por adulto y sus próximas salidas con cupo. */
final readonly class ShopProduct
{
    /** @param  list<ScheduledDeparture>  $departures */
    public function __construct(
        public string $ulid,
        public string $name,
        public ProductType $type,
        public ?string $description,
        public string $destinationCity,
        public string $destinationCountry,
        public ?Money $fromNetPerAdult,
        public array $departures,
    ) {}

    public function departure(string $departureUlid): ?ScheduledDeparture
    {
        foreach ($this->departures as $departure) {
            if ($departure->ulid === $departureUlid) {
                return $departure;
            }
        }

        return null;
    }
}
