<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Actions;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Exceptions\BookingRuleViolation;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Bookings\Services\BookingItemWorkflow;
use App\Modules\Catalog\Contracts\CatalogInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Deja un servicio en espera del proveedor, rechazado o cancelado, con una nota.
 * Al cancelar producto propio se devuelven los cupos apartados. Confirmar tiene su propia acción.
 */
final readonly class ChangeItemStatusAction
{
    public function __construct(
        private BookingItemWorkflow $workflow,
        private CatalogInventory $inventory,
    ) {}

    public function execute(BookingItem $item, BookingItemStatus $next, string $note, CarbonImmutable $now): BookingItem
    {
        if ($next === BookingItemStatus::Confirmed || $next === BookingItemStatus::Pending) {
            throw BookingRuleViolation::invalidTransition($item->status, $next);
        }

        return DB::transaction(function () use ($item, $next, $note, $now): BookingItem {
            $item = BookingItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            $holdUlid = $item->seat_hold_ulid;

            $this->workflow->transition($item, $next, $note, $now);

            if ($next === BookingItemStatus::Cancelled && $holdUlid !== null) {
                $this->inventory->release($holdUlid);
            }

            return $item;
        });
    }
}
