<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un servicio se canceló: la obligación con el proveedor, si no se ha pagado, se anula (Finance). */
final readonly class BookingItemCancelled implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'bookings.item_cancelled';

    public function __construct(public string $itemUlid) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
