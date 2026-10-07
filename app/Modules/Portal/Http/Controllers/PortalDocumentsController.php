<?php

declare(strict_types=1);

namespace App\Modules\Portal\Http\Controllers;

use App\Modules\Bookings\Contracts\TravelerTrips;
use Spatie\LaravelPdf\PdfBuilder;

/** Descargas del portal del viajero (solo con enlace firmado vigente; ver rutas). */
final class PortalDocumentsController
{
    public function itinerary(string $booking, TravelerTrips $trips): PdfBuilder
    {
        abort_unless($trips->trip($booking) instanceof \App\Modules\Bookings\Data\Trip, 404);

        return $trips->itineraryPdf($booking)->download();
    }

    public function voucher(string $booking, string $item, TravelerTrips $trips): PdfBuilder
    {
        return ($trips->voucherPdf($booking, $item) ?? abort(404))->download();
    }
}
