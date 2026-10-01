<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Contracts;

use App\Modules\Shared\Enums\ProductType;
use App\Modules\Suppliers\Data\CommissionTerm;
use App\Modules\Suppliers\Enums\SupplierStanding;
use Carbon\CarbonImmutable;

/** Consulta de proveedores para otros módulos (reservas, precios, finanzas). */
interface SupplierDirectory
{
    public function standing(int $supplierId, CarbonImmutable $date): SupplierStanding;

    public function commissionFor(int $supplierId, ProductType $productType, CarbonImmutable $date): ?CommissionTerm;
}
