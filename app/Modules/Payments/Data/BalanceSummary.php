<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Estado de cuenta del expediente: lo pagado, lo pendiente de confirmar y el saldo con su fecha límite. */
final readonly class BalanceSummary
{
    public function __construct(
        public Money $total,
        public Money $paid,
        public Money $pending,
        public Money $balance,
        public ?CarbonImmutable $dueDate,
        public bool $isOverdue,
    ) {}

    /** Lo que todavía se puede cobrar sin pasarse del total (descuenta también lo pendiente de confirmar). */
    public function collectable(): Money
    {
        $collectable = $this->balance->minus($this->pending);

        return $collectable->isNegative() ? $collectable->multipliedBy(0) : $collectable;
    }
}
