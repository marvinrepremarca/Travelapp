<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Servicio agregado a mano a un expediente directo (sin cotización). El precio de venta no viene del usuario:
 * se calcula en el servidor desde el neto del proveedor con las reglas de Precios.
 *
 * @param  list<int>  $passengerAges  edades a la fecha del servicio
 */
final readonly class DirectItemData
{
    /** @param list<int> $passengerAges */
    public function __construct(
        public string $description,
        public ProductType $productType,
        public ?int $supplierId,
        public ?string $destinationCountry,
        public CarbonImmutable $serviceDate,
        public int $nights,
        public array $passengerAges,
        public Money $supplierNet,
        public SalesChannel $channel,
    ) {}
}
