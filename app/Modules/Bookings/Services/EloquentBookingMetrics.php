<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Bookings\Data\ReceivableBase;
use App\Modules\Bookings\Data\UpcomingTrip;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Shared\Contracts\ScopedViewer;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentBookingMetrics implements BookingMetrics
{
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return Booking::query()
            ->visibleTo($viewer)
            ->when($ownerId !== null, static fn(Builder $query) => $query->where('owner_id', $ownerId))
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $until)
            ->count();
    }

    public function upcomingFor(int $ownerId, CarbonImmutable $fromDate, CarbonImmutable $untilDate, int $limit): array
    {
        $from = $fromDate->toDateString();
        $until = $untilDate->toDateString();
        $closed = $this->closedStatuses();

        return array_values(Booking::query()
            ->where('owner_id', $ownerId)
            ->whereHas('items', static fn(Builder $items) => $items->whereNotIn('status', $closed)->whereBetween('service_date', [$from, $until]))
            ->withMin(['items as next_service_date' => static fn(Builder $items) => $items->whereNotIn('status', $closed)->where('service_date', '>=', $from)], 'service_date')
            ->with('customer:id,display_name')
            ->orderBy('next_service_date')
            ->limit($limit)
            ->get(['id', 'ulid', 'number', 'customer_id'])
            ->map(static fn(Booking $booking): UpcomingTrip => new UpcomingTrip(
                $booking->ulid,
                (string) $booking->number,
                $booking->customer->display_name,
                CarbonImmutable::parse((string) $booking->getAttribute('next_service_date')),
            ))
            ->all());
    }

    public function receivableBases(ScopedViewer $viewer, string $currency, int $limit): array
    {
        return array_values(Booking::query()
            ->visibleTo($viewer)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::InProgress, BookingStatus::NeedsAttention])
            ->where('sale_currency', $currency)
            ->with(['customer:id,display_name', 'items'])
            ->limit($limit)
            ->get()
            ->map(static function (Booking $booking) use ($currency): ReceivableBase {
                $total = $booking->saleTotal()->plus($booking->items->reduce(
                    static fn(Money $carry, BookingItem $item): Money => $item->penaltyAmount() instanceof Money ? $carry->plus($item->penaltyAmount()) : $carry,
                    Money::zero($currency),
                ));
                $firstService = $booking->items->reject(static fn(BookingItem $item): bool => $item->status->isClosed())->min('service_date');

                return new ReceivableBase(
                    $booking->ulid,
                    (string) $booking->number,
                    $booking->customer->display_name,
                    $total,
                    $firstService instanceof CarbonImmutable ? $firstService : null,
                );
            })
            ->all());
    }

    /** @return list<BookingItemStatus> */
    private function closedStatuses(): array
    {
        return array_values(array_filter(BookingItemStatus::cases(), static fn(BookingItemStatus $status): bool => $status->isClosed()));
    }
}
