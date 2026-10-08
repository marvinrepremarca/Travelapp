<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un servicio quedó confirmado con su proveedor: nace la obligación de pago (Finance). */
final readonly class BookingItemConfirmed implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'bookings.item_confirmed';

    public function __construct(
        public string $itemUlid,
        public string $bookingUlid,
        public string $bookingNumber,
        public int $ownerId,
        public ?int $branchId,
        public ?int $supplierId,
        public string $description,
        public int $netAmountMinor,
        public string $netCurrency,
        public CarbonImmutable $serviceDate,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
