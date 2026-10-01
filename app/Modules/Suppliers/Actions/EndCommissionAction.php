<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\SupplierCommission;
use Carbon\CarbonImmutable;

/** Cierra la vigencia de una comisión (no se borra: las reservas pasadas la referencian). */
final class EndCommissionAction
{
    public function execute(SupplierCommission $commission, CarbonImmutable $lastDay): SupplierCommission
    {
        if ($lastDay->lessThan($commission->valid_from)) {
            throw SupplierRuleViolation::invalidValidity();
        }

        $commission->valid_until = $lastDay->startOfDay();
        $commission->save();

        return $commission;
    }
}
