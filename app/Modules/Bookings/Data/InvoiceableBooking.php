<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Bookings\Enums\BookingStatus;
use Brick\Money\Money;

/** Lo que Invoicing necesita para facturar un expediente: cabecera y servicios vigentes desglosados. */
final readonly class InvoiceableBooking
{
    /** @param  list<InvoiceableLine>  $lines */
    public function __construct(
        public string $ulid,
        public string $number,
        public string $title,
        public BookingStatus $status,
        public int $customerId,
        public int $ownerId,
        public ?int $branchId,
        public string $currency,
        public array $lines,
    ) {}

    public function total(): Money
    {
        return array_reduce($this->lines, static fn(Money $carry, InvoiceableLine $line): Money => $carry->plus($line->total()), Money::zero($this->currency));
    }
}
