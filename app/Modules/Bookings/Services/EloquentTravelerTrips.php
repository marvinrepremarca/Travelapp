<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\TravelerTrips;
use App\Modules\Bookings\Data\Trip;
use App\Modules\Bookings\Data\TripService;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use Illuminate\Database\Eloquent\Builder;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class EloquentTravelerTrips implements TravelerTrips
{
    public function __construct(private BookingDocuments $documents) {}

    public function trip(string $bookingUlid): ?Trip
    {
        return $this->map(Booking::query()->where('ulid', $bookingUlid));
    }

    public function findByNumber(string $number): ?Trip
    {
        return $this->map(Booking::query()->where('number', mb_strtoupper(trim($number))));
    }

    public function itineraryPdf(string $bookingUlid): PdfBuilder
    {
        return $this->documents->itinerary(Booking::query()->where('ulid', $bookingUlid)->firstOrFail());
    }

    public function voucherPdf(string $bookingUlid, string $itemUlid): ?PdfBuilder
    {
        $item = BookingItem::query()
            ->whereHas('booking', static fn(Builder $booking) => $booking->where('ulid', $bookingUlid))
            ->where('ulid', $itemUlid)
            ->where('status', BookingItemStatus::Confirmed)
            ->first();

        return $item instanceof BookingItem ? $this->documents->voucher($item) : null;
    }

    /** @param  Builder<Booking>  $query */
    private function map(Builder $query): ?Trip
    {
        $booking = $query->with(['customer:id,display_name', 'items'])->first();
        if (! $booking instanceof Booking) {
            return null;
        }

        return new Trip(
            $booking->ulid,
            (string) $booking->number,
            $booking->title,
            $booking->status,
            $booking->customer_id,
            $booking->customer->display_name,
            $booking->owner_id,
            $booking->branch_id,
            array_values($booking->items->map(static fn(BookingItem $item): TripService => new TripService(
                $item->ulid,
                $item->service_date,
                $item->nights,
                $item->description,
                $item->product_type,
                $item->status,
                $item->supplier_confirmation,
            ))->all()),
        );
    }
}
