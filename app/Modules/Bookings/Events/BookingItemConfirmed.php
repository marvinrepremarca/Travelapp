<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** Un servicio quedó confirmado con su proveedor: nace la obligación de pago (Finance). */
final readonly class BookingItemConfirmed implements ShouldDispatchAfterCommit
{
    use Dispatchable;

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
}
