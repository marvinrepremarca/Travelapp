<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Bookings\Data\ProfitLine;
use App\Modules\Finance\Data\BookingProfit;
use App\Modules\Finance\Data\ProfitabilityReport;
use App\Modules\Finance\Data\ProfitFigures;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Suppliers\Contracts\SupplierDirectory;
use App\Modules\Suppliers\Data\CommissionTerm;
use App\Modules\Suppliers\Enums\CommissionBase;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Rentabilidad por expediente: venta vigente, costo, margen, comisión esperada de los proveedores
 * (pactada a la fecha de venta) y penalidades; con totales por asesor y por sucursal.
 */
final class ProfitabilityCalculator
{
    private const NO_BRANCH = 0;

    /** @var array<string, ?CommissionTerm> */
    private array $terms = [];

    public function __construct(
        private readonly BookingProfitLines $lines,
        private readonly SupplierDirectory $suppliers,
    ) {}

    public function report(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $branchId = null, ?int $ownerId = null): ProfitabilityReport
    {
        /** @var array<string, BookingProfit> $bookings */
        $bookings = [];
        $byOwner = [];
        $byBranch = [];
        $totals = [];

        foreach ($this->lines->soldBetween($viewer, $from, $until, $branchId, $ownerId) as $line) {
            $figures = new ProfitFigures($line->sale, $line->cost, $this->commission($line), $line->penalties);
            $currency = $figures->currency();
            $branchKey = $line->branchId ?? self::NO_BRANCH;

            $previous = $bookings[$line->bookingUlid] ?? null;
            $bookings[$line->bookingUlid] = new BookingProfit(
                $line->bookingUlid,
                $line->bookingNumber,
                $line->bookingTitle,
                $line->ownerId,
                $line->branchId,
                $line->soldAt,
                $previous instanceof BookingProfit ? $previous->figures->plus($figures) : $figures,
            );
            $byOwner[$line->ownerId][$currency] = ($byOwner[$line->ownerId][$currency] ?? ProfitFigures::zero($currency))->plus($figures);
            $byBranch[$branchKey][$currency] = ($byBranch[$branchKey][$currency] ?? ProfitFigures::zero($currency))->plus($figures);
            $totals[$currency] = ($totals[$currency] ?? ProfitFigures::zero($currency))->plus($figures);
        }

        return new ProfitabilityReport(array_values($bookings), $byOwner, $byBranch, $totals);
    }

    /** Comisión pactada sobre la tarifa pública (venta) o sobre el neto (costo), en la moneda de venta. */
    private function commission(ProfitLine $line): Money
    {
        $zero = Money::zero($line->sale->getCurrency());
        if ($line->supplierId === null || $line->sale->isZero()) {
            return $zero;
        }

        $term = $this->termFor($line->supplierId, $line);
        if (! $term instanceof CommissionTerm) {
            return $zero;
        }

        return $term->rate->applyTo($term->base === CommissionBase::Gross ? $line->sale : $line->cost);
    }

    private function termFor(int $supplierId, ProfitLine $line): ?CommissionTerm
    {
        $key = implode('|', [$supplierId, $line->productType->value, $line->soldAt->toDateString()]);
        if (! array_key_exists($key, $this->terms)) {
            $this->terms[$key] = $this->suppliers->commissionFor($supplierId, $line->productType, $line->soldAt);
        }

        return $this->terms[$key];
    }
}
