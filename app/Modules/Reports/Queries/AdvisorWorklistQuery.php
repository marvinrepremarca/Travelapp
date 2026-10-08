<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Bookings\Data\UpcomingTrip;
use App\Modules\Crm\Contracts\LeadMetrics;
use App\Modules\Crm\Data\OpenLead;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Contracts\QuoteMetrics;
use App\Modules\Quotes\Data\ExpiringQuote;
use Carbon\CarbonImmutable;

/** Pendientes del asesor: cada lista la aporta su capacidad y queda vacía si está apagada (ADR-0007). */
final readonly class AdvisorWorklistQuery
{
    public function __construct(
        private QuoteMetrics $quotes,
        private LeadMetrics $leads,
        private BookingMetrics $bookings,
    ) {}

    /** @return list<ExpiringQuote> */
    public function expiringQuotes(User $advisor, CarbonImmutable $now): array
    {
        return $this->quotes->expiringFor($advisor->id, $now, $now->addDays(config()->integer('travel.reports.expiring_quote_days')), $this->limit());
    }

    /** @return list<OpenLead> */
    public function openLeads(User $advisor): array
    {
        return $this->leads->openFor($advisor->id, $this->limit());
    }

    /**
     * Expedientes del asesor con servicios vigentes en los próximos N días ⚙ (fecha local del destino).
     *
     * @return list<UpcomingTrip>
     */
    public function upcomingTrips(User $advisor, CarbonImmutable $today): array
    {
        return $this->bookings->upcomingFor($advisor->id, $today, $today->addDays(config()->integer('travel.reports.upcoming_trip_days')), $this->limit());
    }

    private function limit(): int
    {
        return config()->integer('travel.reports.list_size');
    }
}
