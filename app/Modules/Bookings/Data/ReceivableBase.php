<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Lo que el cliente debe por un expediente vigente, antes de descontar lo cobrado. */
final readonly class ReceivableBase
{
    public function __construct(
        public string $bookingUlid,
        public string $bookingNumber,
        public string $customerName,
        public Money $total,
        public ?CarbonImmutable $firstServiceDate,
    ) {}
}
