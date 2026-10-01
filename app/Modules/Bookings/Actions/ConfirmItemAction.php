<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Services\BookingItemWorkflow;
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
    ) {}

    public function execute(BookingItem $item, string $confirmationCode, ?string $departureUlid, CarbonImmutable $now): BookingItem
    {
        return DB::transaction(function () use ($item, $confirmationCode, $departureUlid, $now): BookingItem {
            $item = BookingItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            if (! $item->status->canTransitionTo(BookingItemStatus::Confirmed)) {
                throw BookingRuleViolation::invalidTransition($item->status, BookingItemStatus::Confirmed);
            }

            if ($item->isOwnProduct()) {
                $this->holdSeats($item, $departureUlid);
            }

            $item->supplier_confirmation = $confirmationCode;
            $this->workflow->transition($item, BookingItemStatus::Confirmed, null, $now);

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
