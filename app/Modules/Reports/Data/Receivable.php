<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Saldo por cobrar de un expediente con su fecha límite de pago. */
final readonly class Receivable
{
    public function __construct(
        public string $bookingUlid,
        public string $bookingNumber,
        public string $customerName,
        public Money $balance,
        public ?CarbonImmutable $dueDate,
    ) {}
}
