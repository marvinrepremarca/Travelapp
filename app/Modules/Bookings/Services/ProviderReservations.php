<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Services;

use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Models\BookingItemPassenger;
use App\Modules\Search\Contracts\SupplierGateway;
use App\Modules\Search\Data\ProviderBookingRequest;

/**
 * Reserva y cancela con el proveedor integrado de un servicio (regla integrations.md):
 * re-cotiza antes de reservar, nunca absorbe un cambio de precio en silencio y usa la misma clave de idempotencia en cada intento.
 * Se llama siempre fuera de transacciones de base de datos.
 */
final readonly class ProviderReservations
{
    private const IDEMPOTENCY_PREFIX = 'booking-item:';

    public function __construct(private SupplierGateway $gateway) {}

    /** @return string referencia de la reserva en el proveedor */
    public function reserve(BookingItem $item): string
    {
        $item->loadMissing(['passengers.traveler:id,first_name,last_name', 'booking:id,number']);
        if ($item->passengers->isEmpty()) {
            throw BookingRuleViolation::passengersRequiredForProvider();
        }

        $current = $this->gateway->reprice($item->product_type, (string) $item->provider_key, (string) $item->provider_offer_id);
        if (!$current instanceof \Brick\Money\Money) {
            throw BookingRuleViolation::offerNoLongerAvailable();
        }

        if (! $current->isEqualTo($item->netAmount())) {
            throw BookingRuleViolation::providerPriceChanged($item->netAmount(), $current);
        }

        return $this->gateway->book($item->product_type, (string) $item->provider_key, new ProviderBookingRequest(
            offerId: (string) $item->provider_offer_id,
            idempotencyKey: self::IDEMPOTENCY_PREFIX . $item->ulid,
            passengerNames: array_values($item->passengers->map(static fn(BookingItemPassenger $passenger): string => $passenger->traveler->fullName())->all()),
            agencyReference: (string) $item->booking->number,
        ));
    }

    public function cancel(BookingItem $item): void
    {
        if ($item->provider_booking_reference !== null) {
            $this->gateway->cancel($item->product_type, (string) $item->provider_key, $item->provider_booking_reference);
        }
    }
}
