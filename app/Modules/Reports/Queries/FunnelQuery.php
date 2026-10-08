<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Crm\Contracts\LeadMetrics;
use App\Modules\Quotes\Contracts\QuoteMetrics;
use App\Modules\Reports\Data\Funnel;
use App\Modules\Reports\Data\Period;
use App\Modules\Shared\Contracts\ScopedViewer;

/**
 * KPI Conversión de cotizaciones (skill reports-dashboard), dentro del alcance de quien consulta.
 * - Leads y expedientes: creados en el período. Cotizaciones enviadas: con su primera versión enviada en el período.
 * - Aceptadas: aceptadas en el período (aunque se hayan enviado antes).
 * Cada etapa la aporta su capacidad; si está apagada, cuenta cero (ADR-0007).
 */
final readonly class FunnelQuery
{
    public function __construct(
        private LeadMetrics $leads,
        private QuoteMetrics $quotes,
        private BookingMetrics $bookings,
    ) {}

    public function for(ScopedViewer $viewer, Period $period, ?int $ownerId = null): Funnel
    {
        return new Funnel(
            leads: $this->leads->createdBetween($viewer, $period->from(), $period->until(), $ownerId),
            quotesSent: $this->quotes->firstSentBetween($viewer, $period->from(), $period->until(), $ownerId),
            quotesAccepted: $this->quotes->acceptedBetween($viewer, $period->from(), $period->until(), $ownerId),
            bookings: $this->bookings->createdBetween($viewer, $period->from(), $period->until(), $ownerId),
        );
    }
}
