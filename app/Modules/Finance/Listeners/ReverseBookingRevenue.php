<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Bookings\Events\BookingItemCancelled;
use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Models\RevenueEntry;
use Carbon\CarbonImmutable;

/**
 * Un servicio cancelado reversa el ingreso que se había reconocido, con un movimiento inverso en la fecha de la
 * cancelación (el original no se toca). Las penalidades se recaudan para el proveedor: no son ingreso propio.
 */
final class ReverseBookingRevenue
{
    public function handle(BookingItemCancelled $event): void
    {
        $recognized = RevenueEntry::query()
            ->where('booking_item_ulid', $event->itemUlid)
            ->where('entry_type', RevenueEntryType::Recognition)
            ->first();
        $alreadyReversed = RevenueEntry::query()
            ->where('booking_item_ulid', $event->itemUlid)
            ->where('entry_type', RevenueEntryType::Reversal)
            ->exists();
        if (! $recognized instanceof RevenueEntry || $alreadyReversed) {
            return;
        }

        $reversal = new RevenueEntry([
            'booking_item_ulid' => $recognized->booking_item_ulid,
            'booking_number' => $recognized->booking_number,
            'customer_name' => $recognized->customer_name,
            'description' => $recognized->description,
            'amount_minor' => -$recognized->amount_minor,
            'currency' => $recognized->currency,
            'recognized_on' => CarbonImmutable::now(config()->string('travel.agency.timezone'))->toDateString(),
            'owner_id' => $recognized->owner_id,
            'branch_id' => $recognized->branch_id,
        ]);
        $reversal->source = $recognized->source;
        $reversal->entry_type = RevenueEntryType::Reversal;
        $reversal->save();
    }
}
