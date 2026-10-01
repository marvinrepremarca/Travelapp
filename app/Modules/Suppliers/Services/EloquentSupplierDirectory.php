<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Services;

use App\Modules\Shared\Enums\ProductType;
use App\Modules\Suppliers\Contracts\SupplierDirectory;
use App\Modules\Suppliers\Data\CommissionTerm;
use App\Modules\Suppliers\Enums\SupplierStanding;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierCommission;
use Carbon\CarbonImmutable;

final class EloquentSupplierDirectory implements SupplierDirectory
{
    public function standing(int $supplierId, CarbonImmutable $date): SupplierStanding
    {
        $supplier = Supplier::query()->find($supplierId);

        return $supplier === null
            ? SupplierStanding::Inactive
            : $supplier->standingOn($date, config()->integer('travel.suppliers.rnt_expiry_warning_days'));
    }

    public function commissionFor(int $supplierId, ProductType $productType, CarbonImmutable $date): ?CommissionTerm
    {
        $commission = SupplierCommission::query()
            ->where('supplier_id', $supplierId)
            ->where('product_type', $productType)
            ->where('valid_from', '<=', $date->toDateString())
            ->where(static fn($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $date->toDateString()))
            ->first();

        return $commission === null ? null : new CommissionTerm($commission->rate(), $commission->base);
    }
}
