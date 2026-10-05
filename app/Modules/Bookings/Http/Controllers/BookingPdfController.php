<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Http\Controllers;

use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Services\BookingDocuments;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPdf\PdfBuilder;

/** Descargas del expediente, por alcance del usuario (fuera del alcance: 404). */
final class BookingPdfController
{
    public function voucher(Booking $booking, string $item, BookingDocuments $documents): PdfBuilder
    {
        Gate::authorize('view', $booking);
        $bookingItem = BookingItem::query()->where('booking_id', $booking->id)->where('ulid', $item)->firstOrFail();

        try {
            return $documents->voucher($bookingItem)->download();
        } catch (BookingRuleViolation) {
            abort(404);
        }
    }

    public function itinerary(Booking $booking, BookingDocuments $documents): PdfBuilder
    {
        Gate::authorize('view', $booking);

        return $documents->itinerary($booking)->download();
    }
}
