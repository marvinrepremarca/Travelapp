<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Contracts;

use App\Modules\Bookings\Data\InvoiceableBooking;
use App\Modules\Bookings\Data\InvoiceCandidate;
use App\Modules\Shared\Contracts\ScopedViewer;

/** Lectura de expedientes para facturar (módulo Invoicing). */
interface BookingInvoicing
{
    /**
     * Expedientes confirmados más recientes dentro del alcance.
     *
     * @return list<InvoiceCandidate>
     */
    public function confirmed(ScopedViewer $viewer, int $limit): array;

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException */
    public function invoiceable(string $bookingUlid): InvoiceableBooking;
}
