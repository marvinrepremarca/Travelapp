<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Reports\Data\Period;
use App\Modules\Reports\Data\SalesFigures;
use App\Modules\Reports\Data\SalesReport;
use App\Modules\Shared\Contracts\ScopedViewer;

/**
 * KPI Ventas brutas / Margen bruto / % margen / Ticket promedio (skill reports-dashboard).
 * - Fecha: creación del expediente (venta), corte en la zona de la agencia.
 * - Moneda: la de la agencia; expedientes en otra moneda no se suman (se verán con conversión en una fase posterior).
 * - Cancelados: sus servicios no suman venta ni costo; solo sus penalidades.
 * - Margen = venta − costo con la tasa congelada al cotizar (sin comisiones esperadas; esas están en Rentabilidad).
 */
final readonly class SalesQuery
{
    private const NO_BRANCH = 0;

    public function __construct(private BookingProfitLines $lines) {}

    public function for(ScopedViewer $viewer, Period $period, ?int $ownerId = null): SalesReport
    {
        $currency = config()->string('travel.agency.default_currency');
        $total = SalesFigures::zero($currency);
        $byBranch = [];
        $byOwner = [];
        $byProduct = [];
        $daily = array_fill(1, $period->days(), 0);
        $counted = [];

        foreach ($this->lines->soldBetween($viewer, $period->from(), $period->until(), null, $ownerId) as $line) {
            if ($line->sale->getCurrency()->getCurrencyCode() !== $currency) {
                continue;
            }

            // Un expediente cuenta una vez aunque tenga varias líneas (proveedor / producto).
            $isNewBooking = ! isset($counted[$line->bookingUlid]);
            $counted[$line->bookingUlid] = true;
            $bookings = $isNewBooking ? 1 : 0;
            $branchKey = $line->branchId ?? self::NO_BRANCH;
            $product = $line->productType->value;

            $total = $total->plus($line->sale, $line->cost, $line->penalties, $bookings);
            $byBranch[$branchKey] = ($byBranch[$branchKey] ?? SalesFigures::zero($currency))->plus($line->sale, $line->cost, $line->penalties, $bookings);
            $byOwner[$line->ownerId] = ($byOwner[$line->ownerId] ?? SalesFigures::zero($currency))->plus($line->sale, $line->cost, $line->penalties, $bookings);
            $byProduct[$product] = ($byProduct[$product] ?? SalesFigures::zero($currency))->plus($line->sale, $line->cost, $line->penalties, 0);
            $day = (int) $line->soldAt->setTimezone(config()->string('travel.agency.timezone'))->day;
            $daily[$day] = ($daily[$day] ?? 0) + $line->sale->getMinorAmount()->toInt();
        }

        uasort($byOwner, static fn(SalesFigures $left, SalesFigures $right): int => $right->sale->compareTo($left->sale));

        return new SalesReport($currency, $total, $byBranch, $byOwner, $byProduct, $daily);
    }
}
