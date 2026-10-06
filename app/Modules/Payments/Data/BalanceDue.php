<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Saldo pendiente de un expediente con su fecha límite de pago. */
final readonly class BalanceDue
{
    public function __construct(
        public string $bookingUlid,
        public string $bookingNumber,
        public int $customerId,
        public int $ownerId,
        public ?int $branchId,
        public Money $balance,
        public CarbonImmutable $dueDate,
    ) {}
}
