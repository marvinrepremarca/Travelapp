<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Documents\Contracts\DocumentRenderer;
use Spatie\LaravelPdf\PdfBuilder;

/**
 * Documentos del expediente para el viajero y el proveedor. No muestran neto, margen ni datos sensibles del pasajero
 * (solo nombre completo); el voucher solo existe para servicios confirmados.
 */
final readonly class BookingDocuments
{
    private const VOUCHER_VIEW = 'bookings::pdf.voucher';

    private const ITINERARY_VIEW = 'bookings::pdf.itinerary';

    public function __construct(private DocumentRenderer $renderer) {}

    public function voucher(BookingItem $item): PdfBuilder
    {
        return $this->renderer->pdf(self::VOUCHER_VIEW, $this->voucherData($item), __('bookings.pdf.voucher_filename', ['number' => $item->booking->number, 'code' => $item->supplier_confirmation]));
    }

    public function voucherHtml(BookingItem $item): string
    {
        return $this->renderer->html(self::VOUCHER_VIEW, $this->voucherData($item));
    }

    public function itinerary(Booking $booking): PdfBuilder
    {
        return $this->renderer->pdf(self::ITINERARY_VIEW, $this->itineraryData($booking), __('bookings.pdf.itinerary_filename', ['number' => $booking->number]));
    }

    public function itineraryHtml(Booking $booking): string
    {
        return $this->renderer->html(self::ITINERARY_VIEW, $this->itineraryData($booking));
    }

    /** @return array<string, mixed> */
    private function voucherData(BookingItem $item): array
    {
        if ($item->status !== BookingItemStatus::Confirmed) {
            throw BookingRuleViolation::voucherRequiresConfirmation();
        }

        $item->loadMissing(['booking.customer:id,display_name', 'passengers.traveler:id,first_name,last_name']);

        return [
            'documentTitle' => __('bookings.pdf.voucher_title', ['code' => $item->supplier_confirmation]),
            'item' => $item,
            'booking' => $item->booking,
        ];
    }

    /** @return array<string, mixed> */
    private function itineraryData(Booking $booking): array
    {
        $booking->loadMissing(['customer:id,display_name', 'items.passengers.traveler:id,first_name,last_name']);
        $days = $booking->items
            ->reject(static fn(BookingItem $item): bool => $item->status->isClosed())
            ->groupBy(static fn(BookingItem $item): string => $item->service_date->toDateString())
            ->sortKeys();

        return [
            'documentTitle' => __('bookings.pdf.itinerary_title', ['number' => $booking->number]),
            'booking' => $booking,
            'days' => $days,
        ];
    }
}
