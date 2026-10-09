<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Data\DirectItemData;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Services\BookingItemWorkflow;
use App\Modules\Pricing\Contracts\PriceCalculator;
use App\Modules\Pricing\Data\PriceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Agrega un servicio a un expediente directo. El precio de venta se calcula en el servidor (Precios) y queda
 * congelado en el servicio, igual que en un expediente que viene de una cotización.
 */
final readonly class AddDirectItemAction
{
    private const DIRECT_KIND = 'manual';

    public function __construct(
        private PriceCalculator $calculator,
        private BookingItemWorkflow $workflow,
    ) {}

    public function execute(Booking $booking, DirectItemData $data, CarbonImmutable $now): BookingItem
    {
        if ($booking->quote_ulid !== null) {
            throw BookingRuleViolation::itemsComeFromQuote();
        }

        if ($booking->status === BookingStatus::Cancelled) {
            throw BookingRuleViolation::bookingClosed();
        }

        if ($data->passengerAges === [] || ! $data->supplierNet->isPositive()) {
            throw BookingRuleViolation::directItemInvalid();
        }

        $breakdown = $this->calculator->calculate(new PriceRequest(
            supplierNet: $data->supplierNet,
            saleCurrency: $booking->sale_currency,
            productType: $data->productType,
            channel: $data->channel,
            serviceDate: $data->serviceDate,
            passengers: count($data->passengerAges),
            nights: $data->nights,
            supplierId: $data->supplierId,
            destinationCountry: $data->destinationCountry,
        ));

        return DB::transaction(function () use ($booking, $data, $breakdown, $now): BookingItem {
            $item = new BookingItem([
                'booking_id' => $booking->id,
                'kind' => self::DIRECT_KIND,
                'product_type' => $data->productType,
                'description' => $data->description,
                'supplier_id' => $data->supplierId,
                'destination_country' => $data->destinationCountry,
                'service_date' => $data->serviceDate->toDateString(),
                'nights' => $data->nights,
                'passenger_ages' => $data->passengerAges,
            ]);
            $item->net_amount_minor = $data->supplierNet->getMinorAmount()->toInt();
            $item->net_currency = $data->supplierNet->getCurrency()->getCurrencyCode();
            $item->sale_amount_minor = $breakdown->total()->getMinorAmount()->toInt();
            $item->margin_amount_minor = $breakdown->margin()->getMinorAmount()->toInt();
            $item->price_breakdown = $breakdown->snapshot($booking->sale_currency);
            $item->status = BookingItemStatus::Pending;
            $item->status_changed_at = $now;
            $item->save();

            $this->workflow->syncBooking($booking);

            return $item;
        });
    }
}
