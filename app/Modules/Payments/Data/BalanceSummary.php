<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/**
 * Estado de cuenta del expediente. `total` = servicios vigentes + penalidades registradas (lo que el cliente debe).
 * `balance` = total − pagado + reembolsado. Saldo negativo = la agencia le debe al cliente.
 */
final readonly class BalanceSummary
{
    public function __construct(
        public Money $total,
        public Money $paid,
        public Money $pending,
        public Money $refunded,
        public Money $refundsInProgress,
        public Money $balance,
        public ?CarbonImmutable $dueDate,
        public bool $isOverdue,
    ) {}

    /** Lo que todavía se puede cobrar sin pasarse del total (descuenta también lo pendiente de confirmar). */
    public function collectable(): Money
    {
        return $this->atLeastZero($this->balance->minus($this->pending));
    }

    /** Lo que se puede devolver: lo pagado de más, descontando reembolsos ya pedidos o aprobados. */
    public function refundable(): Money
    {
        return $this->atLeastZero($this->balance->negated()->minus($this->refundsInProgress));
    }

    private function atLeastZero(Money $amount): Money
    {
        return $amount->isNegative() ? $amount->multipliedBy(0) : $amount;
    }
}
