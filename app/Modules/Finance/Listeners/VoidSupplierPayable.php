<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Bookings\Events\BookingItemCancelled;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Models\SupplierPayable;

/** Servicio cancelado: si su obligación sigue pendiente, se anula. Si ya se pagó, queda para gestionar el reintegro. */
final class VoidSupplierPayable
{
    public function handle(BookingItemCancelled $event): void
    {
        $payable = SupplierPayable::query()->where('booking_item_ulid', $event->itemUlid)->where('status', PayableStatus::Open)->first();
        if ($payable instanceof SupplierPayable) {
            $payable->status = PayableStatus::Voided;
            $payable->save();
        }
    }
}
