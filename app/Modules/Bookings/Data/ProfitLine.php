<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Venta y costo de un expediente agrupados por proveedor y tipo de producto, para la rentabilidad (Finance).
 * Todo en la moneda de venta: el costo es venta − margen con la tasa congelada al cotizar.
 */
final readonly class ProfitLine
{
    public function __construct(
        public string $bookingUlid,
        public string $bookingNumber,
        public string $bookingTitle,
        public int $ownerId,
        public ?int $branchId,
        public CarbonImmutable $soldAt,
        public ?int $supplierId,
        public ProductType $productType,
        public Money $sale,
        public Money $cost,
        public Money $penalties,
    ) {}
}
