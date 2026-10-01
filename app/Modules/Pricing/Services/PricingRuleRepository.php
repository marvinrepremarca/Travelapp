<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Pricing\Data\PriceRequest;
use App\Modules\Pricing\Models\FeeRule;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Pricing\Models\TaxRule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Carga las reglas activas y vigentes que pueden aplicar a una solicitud de precio. */
final class PricingRuleRepository
{
    /** Regla de markup ganadora: la más específica; a igualdad, mayor prioridad y luego la más reciente. */
    public function markupFor(PriceRequest $request): ?MarkupRule
    {
        return $this->inForce(MarkupRule::query(), $request->serviceDate)
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'product_type', $request->productType->value))
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'supplier_id', $request->supplierId))
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'destination_country', $request->destinationCountry === null ? null : mb_strtoupper($request->destinationCountry)))
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'sales_channel', $request->channel->value))
            ->get()
            ->sortBy([
                static fn(MarkupRule $a, MarkupRule $b): int => $b->specificity() <=> $a->specificity(),
                static fn(MarkupRule $a, MarkupRule $b): int => $b->priority <=> $a->priority,
                static fn(MarkupRule $a, MarkupRule $b): int => $b->valid_from <=> $a->valid_from,
            ])
            ->first();
    }

    /** @return Collection<int, FeeRule> */
    public function feesFor(PriceRequest $request): Collection
    {
        return $this->inForce(FeeRule::query(), $request->serviceDate)
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'product_type', $request->productType->value))
            ->where(fn(Builder $query) => $this->nullOrEqual($query, 'sales_channel', $request->channel->value))
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, TaxRule> */
    public function taxesFor(PriceRequest $request): Collection
    {
        return $this->inForce(TaxRule::query(), $request->serviceDate)
            ->orderBy('id')
            ->get()
            ->reject(static fn(TaxRule $tax): bool => $tax->exempts($request->productType))
            ->values();
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    private function inForce(Builder $query, CarbonImmutable $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('valid_from', '<=', $date->toDateString())
            ->where(static fn(Builder $inner) => $inner->whereNull('valid_until')->orWhere('valid_until', '>=', $date->toDateString()));
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function nullOrEqual(Builder $query, string $column, int|string|null $value): void
    {
        $query->whereNull($column);

        if ($value !== null) {
            $query->orWhere($column, $value);
        }
    }
}
