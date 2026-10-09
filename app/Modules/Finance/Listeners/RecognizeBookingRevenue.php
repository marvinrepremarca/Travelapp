<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Finance\Models\RevenueEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Base de causación: el ingreso nace cuando el servicio queda confirmado, por su precio de venta, en la fecha
 * de la agencia. Idempotente por servicio; si Contabilidad estuvo apagada se reconoce al reprocesar la bitácora.
 */
final class RecognizeBookingRevenue
{
    public function handle(BookingItemConfirmed $event): void
    {
        if ($event->saleAmountMinor === null || $event->saleCurrency === null) {
            Log::info('revenue_skipped_without_sale_price', ['booking_item' => $event->itemUlid]);

            return;
        }

        $recognized = RevenueEntry::query()
            ->where('booking_item_ulid', $event->itemUlid)
            ->where('entry_type', RevenueEntryType::Recognition)
            ->exists();
        if ($recognized) {
            return;
        }

        $entry = new RevenueEntry([
            'booking_item_ulid' => $event->itemUlid,
            'booking_number' => $event->bookingNumber,
            'customer_name' => $event->customerName,
            'description' => $event->description,
            'amount_minor' => $event->saleAmountMinor,
            'currency' => $event->saleCurrency,
            'recognized_on' => CarbonImmutable::now(config()->string('travel.agency.timezone'))->toDateString(),
            'owner_id' => $event->ownerId,
            'branch_id' => $event->branchId,
        ]);
        $entry->source = RevenueSource::BookingItem;
        $entry->entry_type = RevenueEntryType::Recognition;
        $entry->save();
    }
}
