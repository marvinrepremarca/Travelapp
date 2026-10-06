<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Contracts\IncomeTaxes;
use App\Modules\Pricing\Models\TaxRule;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\ValueObjects\Percentage;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

/** Las mismas reglas de impuesto que usa el cálculo de precios (vigencia, activas y exenciones por producto). */
final class RuleBasedIncomeTaxes implements IncomeTaxes
{
    public function taxOn(Money $agencyIncome, ?ProductType $productType, CarbonImmutable $date): Money
    {
        $tax = $agencyIncome->multipliedBy(0);
        $rules = TaxRule::query()
            ->where('is_active', true)
            ->where('valid_from', '<=', $date->toDateString())
            ->where(static fn(Builder $query) => $query->whereNull('valid_until')->orWhere('valid_until', '>=', $date->toDateString()))
            ->orderBy('id')
            ->get();

        foreach ($rules as $rule) {
            if (! $productType instanceof ProductType || ! $rule->exempts($productType)) {
                $tax = $tax->plus(Percentage::fromBasisPoints($rule->rate_basis_points)->applyTo($agencyIncome));
            }
        }

        return $tax;
    }
}
