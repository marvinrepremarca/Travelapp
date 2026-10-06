<?php

declare(strict_types=1);

namespace App\Modules\Reports\Queries;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Models\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Pendientes del asesor: cotizaciones por vencer, leads por atender y próximos viajes (solo los suyos). */
final class AdvisorWorklistQuery
{
    /** @return Collection<int, Quote> */
    public function expiringQuotes(User $advisor, CarbonImmutable $now): Collection
    {
        return Quote::query()
            ->where('owner_id', $advisor->id)
            ->where('status', QuoteStatus::Sent)
            ->whereBetween('valid_until', [$now, $now->addDays(config()->integer('travel.reports.expiring_quote_days'))])
            ->with('customer:id,display_name')
            ->orderBy('valid_until')
            ->limit($this->limit())
            ->get(['id', 'ulid', 'number', 'title', 'customer_id', 'valid_until']);
    }

    /** @return Collection<int, Lead> */
    public function openLeads(User $advisor): Collection
    {
        return Lead::query()
            ->where('owner_id', $advisor->id)
            ->whereIn('status', [LeadStatus::New, LeadStatus::Contacted])
            ->orderBy('status_changed_at')
            ->limit($this->limit())
            ->get(['id', 'ulid', 'contact_name', 'destination', 'status', 'status_changed_at']);
    }

    /**
     * Expedientes del asesor con servicios vigentes en los próximos N días ⚙ (fecha local del destino).
     *
     * @return Collection<int, Booking>
     */
    public function upcomingTrips(User $advisor, CarbonImmutable $today): Collection
    {
        $from = $today->toDateString();
        $until = $today->addDays(config()->integer('travel.reports.upcoming_trip_days'))->toDateString();
        $closed = array_values(array_filter(BookingItemStatus::cases(), static fn(BookingItemStatus $status): bool => $status->isClosed()));

        return Booking::query()
            ->where('owner_id', $advisor->id)
            ->whereHas('items', static fn(Builder $items) => $items->whereNotIn('status', $closed)->whereBetween('service_date', [$from, $until]))
            ->withMin(['items as next_service_date' => static fn(Builder $items) => $items->whereNotIn('status', $closed)->where('service_date', '>=', $from)], 'service_date')
            ->with('customer:id,display_name')
            ->orderBy('next_service_date')
            ->limit($this->limit())
            ->get(['id', 'ulid', 'number', 'title', 'customer_id']);
    }

    private function limit(): int
    {
        return config()->integer('travel.reports.list_size');
    }
}
