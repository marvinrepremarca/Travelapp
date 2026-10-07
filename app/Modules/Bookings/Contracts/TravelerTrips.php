<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\Trip;
use Spatie\LaravelPdf\PdfBuilder;

/** Lo que el portal del viajero puede ver y descargar de su expediente. */
interface TravelerTrips
{
    public function trip(string $bookingUlid): ?Trip;

    public function findByNumber(string $number): ?Trip;

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    public function itineraryPdf(string $bookingUlid): PdfBuilder;

    /** Null si el servicio no es del expediente o aún no está confirmado. */
    public function voucherPdf(string $bookingUlid, string $itemUlid): ?PdfBuilder;
}
