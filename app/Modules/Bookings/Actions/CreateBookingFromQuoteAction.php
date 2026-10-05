<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Quotes\Contracts\AcceptedQuotes;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Convierte la opción aceptada en expediente: copia sus servicios con los precios congelados de la versión aceptada.
 * El expediente es del asesor de la cotización y de su sucursal. Una cotización produce un solo expediente.
 */
final readonly class CreateBookingFromQuoteAction
{
    public function __construct(private AcceptedQuotes $acceptedQuotes) {}

    public function execute(string $quoteUlid, CarbonImmutable $now): Booking
    {
        $existing = Booking::query()->where('quote_ulid', $quoteUlid)->value('number');
        if ($existing !== null) {
            throw BookingRuleViolation::alreadyConverted((string) $existing);
        }

        $accepted = $this->acceptedQuotes->acceptedOption($quoteUlid);

        return DB::transaction(static function () use ($accepted, $now): Booking {
            $booking = new Booking([
                'customer_id' => $accepted->customerId,
                'title' => $accepted->title,
                'sale_currency' => $accepted->saleCurrency,
                'quote_ulid' => $accepted->quoteUlid,
                'quote_number' => $accepted->quoteNumber,
                'quote_version' => $accepted->version,
            ]);
            $booking->owner_id = $accepted->ownerId;
            $booking->branch_id = $accepted->branchId;
            $booking->status = BookingStatus::InProgress;
            $booking->save();

            $booking->number = config()->string('travel.bookings.number_prefix')
                . str_pad((string) $booking->id, config()->integer('travel.bookings.number_digits'), '0', STR_PAD_LEFT);
            $booking->save();

            foreach ($accepted->items as $data) {
                $item = new BookingItem([
                    'booking_id' => $booking->id,
                    'kind' => $data['kind'],
                    'product_type' => $data['product_type'],
                    'description' => $data['description'],
                    'catalog_product_ulid' => $data['catalog_product_ulid'],
                    'supplier_id' => $data['supplier_id'],
                    'provider_key' => $data['provider_key'],
                    'provider_offer_id' => $data['provider_offer_id'],
                    'destination_country' => $data['destination_country'],
                    'service_date' => $data['service_date'],
                    'nights' => $data['nights'],
                    'passenger_ages' => $data['passenger_ages'],
                ]);
                // Precios del servidor congelados en la cotización aceptada: no se recalculan.
                $item->net_amount_minor = $data['net_amount_minor'];
                $item->net_currency = $data['net_currency'];
                $item->sale_amount_minor = $data['sale_amount_minor'];
                $item->margin_amount_minor = $data['margin_amount_minor'];
                $item->price_breakdown = $data['price_breakdown'];
                $item->status = BookingItemStatus::Pending;
                $item->status_changed_at = $now;
                $item->save();
            }

            return $booking;
        });
    }
}
