<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Services\BookingItemWorkflow;
use App\Modules\Bookings\Services\ProviderReservations;
use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Catalog\Data\DepartureSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra la confirmación del proveedor con su código. En producto propio aparta el cupo de la salida elegida
 * (todos los pasajeros ocupan cupo); sin cupo no se confirma: no hay sobreventa.
 */
final readonly class ConfirmItemAction
{
    private const HOLD_KEY_PREFIX = 'booking-item:';

    public function __construct(
        private BookingItemWorkflow $workflow,
        private CatalogInventory $inventory,
        private ProviderReservations $providers,
    ) {}

    public function execute(BookingItem $item, ?string $confirmationCode, ?string $departureUlid, CarbonImmutable $now): BookingItem
    {
        if (! $item->status->canTransitionTo(BookingItemStatus::Confirmed)) {
            throw BookingRuleViolation::invalidTransition($item->status, BookingItemStatus::Confirmed);
        }

        // Proveedor integrado: re-cotiza y reserva ANTES de abrir la transacción (nunca HTTP dentro de una transacción).
        $providerReference = $item->isFromProvider() ? $this->providers->reserve($item) : null;
        if ($providerReference === null && ($confirmationCode === null || $confirmationCode === '')) {
            throw BookingRuleViolation::confirmationCodeRequired();
        }

        return DB::transaction(function () use ($item, $confirmationCode, $departureUlid, $now, $providerReference): BookingItem {
            $item = BookingItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            if (! $item->status->canTransitionTo(BookingItemStatus::Confirmed)) {
                throw BookingRuleViolation::invalidTransition($item->status, BookingItemStatus::Confirmed);
            }

            if ($item->isOwnProduct()) {
                $this->holdSeats($item, $departureUlid);
            }

            $item->provider_booking_reference = $providerReference;
            $item->supplier_confirmation = $confirmationCode !== null && $confirmationCode !== '' ? $confirmationCode : $providerReference;
            $this->workflow->transition($item, BookingItemStatus::Confirmed, null, $now);
            $booking = $item->booking()->with('customer:id,display_name')->firstOrFail();

            (new BookingItemConfirmed(
                $item->ulid,
                $booking->ulid,
                (string) $booking->number,
                $booking->owner_id,
                $booking->branch_id,
                $item->supplier_id,
                $item->description,
                $item->net_amount_minor,
                $item->net_currency,
                $item->service_date,
                $item->sale_amount_minor,
                $booking->sale_currency,
                $booking->customer->display_name,
            ))->publish();

            return $item;
        });
    }

    private function holdSeats(BookingItem $item, ?string $departureUlid): void
    {
        if ($departureUlid === null) {
            throw BookingRuleViolation::departureRequired();
        }

        $slots = $this->inventory->departuresOn((string) $item->catalog_product_ulid, $item->service_date);
        $belongs = array_filter($slots, static fn(DepartureSlot $slot): bool => $slot->ulid === $departureUlid) !== [];
        if (! $belongs) {
            throw BookingRuleViolation::departureNotAvailable();
        }

        $item->seat_hold_ulid = $this->inventory->hold($departureUlid, count($item->passenger_ages), (string) $item->booking()->value('number'), self::HOLD_KEY_PREFIX . $item->ulid);
        $item->catalog_departure_ulid = $departureUlid;
    }
}
