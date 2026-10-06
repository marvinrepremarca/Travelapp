<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Contracts\BookingInvoicing;
use App\Modules\Bookings\Data\InvoiceableBooking;
use App\Modules\Bookings\Data\InvoiceableLine;
use App\Modules\Bookings\Data\InvoiceCandidate;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Pricing\Enums\PriceComponentType;
use App\Modules\Shared\Contracts\ScopedViewer;
use Brick\Money\Money;

/**
 * Desglose para facturar con el precio congelado de cada servicio: el ingreso propio son los componentes
 * de la agencia (markup y fees), el IVA es el componente de impuesto y el resto se recauda para el proveedor.
 * El producto propio es todo ingreso de la agencia. Las penalidades de servicios cancelados se recaudan para el proveedor.
 */
final class EloquentBookingInvoicing implements BookingInvoicing
{
    public function confirmed(ScopedViewer $viewer, int $limit): array
    {
        return array_values(Booking::query()
            ->visibleTo($viewer)
            ->where('status', BookingStatus::Confirmed)
            ->with(['customer:id,display_name', 'items'])
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(static fn(Booking $booking): InvoiceCandidate => new InvoiceCandidate(
                $booking->ulid,
                (string) $booking->number,
                $booking->title,
                $booking->customer->display_name,
                $booking->owner_id,
                $booking->branch_id,
                $booking->saleTotal()->plus($booking->items->reduce(
                    static fn(Money $carry, BookingItem $item): Money => $item->penaltyAmount() instanceof Money ? $carry->plus($item->penaltyAmount()) : $carry,
                    Money::zero($booking->sale_currency),
                )),
            ))->all());
    }

    public function invoiceable(string $bookingUlid): InvoiceableBooking
    {
        $booking = Booking::query()->with('items')->where('ulid', $bookingUlid)->firstOrFail();
        $lines = [];
        foreach ($booking->items as $item) {
            $line = $item->status->isClosed() ? $this->penaltyLine($item) : $this->serviceLine($item);
            if ($line instanceof InvoiceableLine) {
                $lines[] = $line;
            }
        }

        return new InvoiceableBooking(
            $booking->ulid,
            (string) $booking->number,
            $booking->title,
            $booking->status,
            $booking->customer_id,
            $booking->owner_id,
            $booking->branch_id,
            $booking->sale_currency,
            $lines,
        );
    }

    private function serviceLine(BookingItem $item): InvoiceableLine
    {
        $sale = $item->saleAmount();
        $currency = $sale->getCurrency();
        $agency = Money::zero($currency);
        $tax = Money::zero($currency);
        foreach ($this->components($item) as [$type, $amountMinor]) {
            if ($type->isAgencyIncome()) {
                $agency = $agency->plus(Money::ofMinor($amountMinor, $currency));
            } elseif ($type === PriceComponentType::Tax) {
                $tax = $tax->plus(Money::ofMinor($amountMinor, $currency));
            }
        }

        // El tercero es lo que queda de la venta: así la factura siempre suma exactamente el precio vendido.
        $thirdParty = $sale->minus($agency)->minus($tax);
        if ($item->isOwnProduct()) {
            $agency = $agency->plus($thirdParty);
            $thirdParty = Money::zero($currency);
        }

        return new InvoiceableLine($item->ulid, $item->description, $item->product_type, $thirdParty, $agency, $tax);
    }

    private function penaltyLine(BookingItem $item): ?InvoiceableLine
    {
        $penalty = $item->penaltyAmount();
        if (! $penalty instanceof Money || $penalty->isZero()) {
            return null;
        }

        $zero = Money::zero($penalty->getCurrency());

        return new InvoiceableLine($item->ulid, __('bookings.invoice_penalty', ['service' => $item->description]), $item->product_type, $penalty, $zero, $zero);
    }

    /** @return list<array{PriceComponentType, int}> */
    private function components(BookingItem $item): array
    {
        /** @var list<array{type?: string, amount_minor?: int}> $components */
        $components = $item->price_breakdown['components'] ?? [];
        $parsed = [];
        foreach ($components as $component) {
            $type = PriceComponentType::tryFrom((string) ($component['type'] ?? ''));
            if ($type instanceof PriceComponentType) {
                $parsed[] = [$type, (int) ($component['amount_minor'] ?? 0)];
            }
        }

        return $parsed;
    }
}
