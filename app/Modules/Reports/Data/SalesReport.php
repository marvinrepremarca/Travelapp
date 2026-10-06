<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

/**
 * Ventas del período en la moneda de la agencia: total, desgloses y serie diaria.
 * Los desgloses usan como clave el id (sucursal, asesor) o el valor del tipo de producto.
 */
final readonly class SalesReport
{
    /**
     * @param  array<int, SalesFigures>  $byBranch  clave 0 = sin sucursal
     * @param  array<int, SalesFigures>  $byOwner
     * @param  array<string, SalesFigures>  $byProduct
     * @param  array<int, int>  $dailySaleMinor  día del mes (1..31) → venta en unidades menores
     */
    public function __construct(
        public string $currency,
        public SalesFigures $total,
        public array $byBranch,
        public array $byOwner,
        public array $byProduct,
        public array $dailySaleMinor,
    ) {}
}
