<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use Brick\Money\Money;

/** Expediente confirmado con el total que el cliente debe pagar (servicios vigentes + penalidades). */
final readonly class InvoiceCandidate
{
    public function __construct(
        public string $ulid,
        public string $number,
        public string $title,
        public string $customerName,
        public int $ownerId,
        public ?int $branchId,
        public Money $total,
    ) {}
}
