<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un servicio se canceló: la obligación con el proveedor, si no se ha pagado, se anula (Finance). */
final readonly class BookingItemCancelled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public string $itemUlid) {}
}
