<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

/**
 * Rentabilidad del período: por expediente y totalizada por asesor, por sucursal y general.
 * Los totales se separan por moneda: nunca se suman importes de monedas distintas.
 */
final readonly class ProfitabilityReport
{
    /**
     * @param  list<BookingProfit>  $bookings
     * @param  array<int, array<string, ProfitFigures>>  $byOwner
     * @param  array<int, array<string, ProfitFigures>>  $byBranch  clave 0 = sin sucursal
     * @param  array<string, ProfitFigures>  $totals
     */
    public function __construct(
        public array $bookings,
        public array $byOwner,
        public array $byBranch,
        public array $totals,
    ) {}
}
