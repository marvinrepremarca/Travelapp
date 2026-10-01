<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\ValueObjects\Percentage;
use App\Modules\Suppliers\Enums\CommissionBase;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierCommission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Pacta una comisión por tipo de producto. No puede solaparse con otra del mismo tipo. */
final class AddCommissionAction
{
    private const FAR_FUTURE = '9999-12-31';

    public function execute(Supplier $supplier, ProductType $productType, Percentage $rate, CommissionBase $base, CarbonImmutable $validFrom, ?CarbonImmutable $validUntil, User $actor): SupplierCommission
    {
        if ($validUntil instanceof CarbonImmutable && $validUntil->lessThan($validFrom)) {
            throw SupplierRuleViolation::invalidValidity();
        }

        return DB::transaction(function () use ($supplier, $productType, $rate, $base, $validFrom, $validUntil, $actor): SupplierCommission {
            $end = ($validUntil ?? CarbonImmutable::parse(self::FAR_FUTURE))->toDateString();

            $overlaps = SupplierCommission::query()
                ->where('supplier_id', $supplier->id)
                ->where('product_type', $productType)
                ->where('valid_from', '<=', $end)
                ->where(static fn($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $validFrom->toDateString()))
                ->lockForUpdate()
                ->exists();

            if ($overlaps) {
                throw SupplierRuleViolation::overlappingCommission();
            }

            return $supplier->commissions()->create([
                'product_type' => $productType,
                'rate_basis_points' => $rate->basisPoints,
                'base' => $base,
                'valid_from' => $validFrom->toDateString(),
                'valid_until' => $validUntil?->toDateString(),
                'created_by' => $actor->id,
            ]);
        });
    }
}
