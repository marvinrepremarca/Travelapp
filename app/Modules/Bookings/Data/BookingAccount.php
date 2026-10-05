<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Lo que otros módulos (Payments, Finance) necesitan saber de un expediente para cobrarlo. */
final readonly class BookingAccount
{
    public function __construct(
        public string $ulid,
        public string $number,
        public string $title,
        public int $customerId,
        public string $customerName,
        public int $ownerId,
        public ?int $branchId,
        public Money $saleTotal,
        public Money $penaltiesTotal,
        public ?CarbonImmutable $firstServiceDate,
    ) {}
}
