<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Bookings\Data\ManifestPassenger;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Models\BookingItemPassenger;
use App\Modules\Shared\Support\Mask;

final class EloquentDepartureManifests implements DepartureManifests
{
    public function passengersOf(string $departureUlid): array
    {
        $items = BookingItem::query()
            ->where('catalog_departure_ulid', $departureUlid)
            ->where('status', BookingItemStatus::Confirmed)
            ->with(['booking:id,ulid,number,customer_id', 'booking.customer:id,phone', 'passengers.traveler'])
            ->get();

        $passengers = [];
        foreach ($items as $item) {
            foreach ($item->passengers as $passenger) {
                $passengers[] = $this->map($item, $passenger);
            }
        }

        usort($passengers, static fn(ManifestPassenger $left, ManifestPassenger $right): int => [$left->bookingNumber, $left->travelerName] <=> [$right->bookingNumber, $right->travelerName]);

        return $passengers;
    }

    public function countsFor(array $departureUlids): array
    {
        if ($departureUlids === []) {
            return [];
        }

        return BookingItem::query()
            ->whereIn('catalog_departure_ulid', $departureUlids)
            ->where('status', BookingItemStatus::Confirmed)
            ->join('booking_item_passengers', 'booking_item_passengers.booking_item_id', '=', 'booking_items.id')
            ->toBase()
            ->selectRaw('catalog_departure_ulid, count(*) as passengers')
            ->groupBy('catalog_departure_ulid')
            ->pluck('passengers', 'catalog_departure_ulid')
            ->map(static fn(mixed $count): int => (int) $count)
            ->all();
    }

    private function map(BookingItem $item, BookingItemPassenger $passenger): ManifestPassenger
    {
        $traveler = $passenger->traveler;

        return new ManifestPassenger(
            bookingNumber: (string) $item->booking->number,
            bookingUlid: $item->booking->ulid,
            travelerName: trim($traveler->first_name . ' ' . $traveler->last_name),
            age: $passenger->age_at_service,
            passengerType: $passenger->passenger_type->label(),
            nationality: $traveler->nationality,
            maskedDocument: Mask::value($traveler->passport_number),
            contactPhone: $item->booking->customer->phone,
        );
    }
}
