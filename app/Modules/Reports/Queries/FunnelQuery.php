<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Models\Booking;
use App\Modules\Crm\Models\Lead;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Models\QuoteVersion;
use App\Modules\Reports\Data\Funnel;
use App\Modules\Reports\Data\Period;
use App\Modules\Shared\Contracts\ScopedViewer;
use Illuminate\Database\Eloquent\Builder;

/**
 * KPI Conversión de cotizaciones (skill reports-dashboard), dentro del alcance de quien consulta.
 * - Leads y expedientes: creados en el período. Cotizaciones enviadas: con su primera versión enviada en el período.
 * - Aceptadas: aceptadas en el período (aunque se hayan enviado antes).
 */
final class FunnelQuery
{
    private const FIRST_VERSION = 1;

    public function for(ScopedViewer $viewer, Period $period, ?int $ownerId = null): Funnel
    {
        $owner = static fn(Builder $query) => $ownerId === null ? $query : $query->where('owner_id', $ownerId);

        return new Funnel(
            leads: $owner(Lead::query()->visibleTo($viewer)->whereBetween('created_at', [$period->from(), $period->until()]))->count(),
            quotesSent: $owner(Quote::query()->visibleTo($viewer)->whereIn('id', QuoteVersion::query()
                ->select('quote_id')
                ->where('version', self::FIRST_VERSION)
                ->where('sent_at', '>=', $period->from())
                ->where('sent_at', '<', $period->until())))->count(),
            quotesAccepted: $owner(Quote::query()->visibleTo($viewer)->where('accepted_at', '>=', $period->from())->where('accepted_at', '<', $period->until()))->count(),
            bookings: $owner(Booking::query()->visibleTo($viewer)->where('created_at', '>=', $period->from())->where('created_at', '<', $period->until()))->count(),
        );
    }
}
