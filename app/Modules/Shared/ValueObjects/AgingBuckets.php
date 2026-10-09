<?php

declare(strict_types=1);

namespace App\Modules\Shared\ValueObjects;

use Brick\Money\Money;

/** Saldos agrupados por vencimiento: vencido, vence pronto (≤ N días ⚙) y posterior. */
final readonly class AgingBuckets
{
    public function __construct(
        public Money $overdue,
        public Money $dueSoon,
        public Money $later,
        public int $count,
    ) {}

    public static function zero(string $currency): self
    {
        return new self(Money::zero($currency), Money::zero($currency), Money::zero($currency), 0);
    }

    public function total(): Money
    {
        return $this->overdue->plus($this->dueSoon)->plus($this->later);
    }
}
