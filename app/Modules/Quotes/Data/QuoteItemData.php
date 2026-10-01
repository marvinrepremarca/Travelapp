<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Data;

use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Lo que el asesor define de un ítem; neto, venta y margen los calcula el servidor. */
final readonly class QuoteItemData
{
    /**
     * @param  list<int>  $passengerAges  edad de cada pasajero a la fecha del servicio
     */
    public function __construct(
        public QuoteItemKind $kind,
        public CarbonImmutable $serviceDate,
        public array $passengerAges,
        public int $nights = 0,
        public ?string $catalogProductUlid = null,
        public ?ProductType $productType = null,
        public ?string $description = null,
        public ?Money $manualNet = null,
        public ?int $supplierId = null,
        public ?string $destinationCountry = null,
    ) {}
}
