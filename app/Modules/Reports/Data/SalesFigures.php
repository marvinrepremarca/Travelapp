<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/** Ventas, costo y margen de un conjunto de expedientes en una moneda. Margen y % son derivados. */
final readonly class SalesFigures
{
    private const PERCENT = 100;

    private const RATE_SCALE = 1;

    public function __construct(
        public Money $sale,
        public Money $cost,
        public Money $penalties,
        public int $bookings,
    ) {}

    public static function zero(string $currency): self
    {
        return new self(Money::zero($currency), Money::zero($currency), Money::zero($currency), 0);
    }

    public function plus(Money $sale, Money $cost, Money $penalties, int $bookings): self
    {
        return new self($this->sale->plus($sale), $this->cost->plus($cost), $this->penalties->plus($penalties), $this->bookings + $bookings);
    }

    public function margin(): Money
    {
        return $this->sale->minus($this->cost);
    }

    public function marginRate(): ?string
    {
        return $this->sale->isZero() ? null : $this->ratio($this->margin(), $this->sale);
    }

    /** Ticket promedio = ventas / expedientes. */
    public function averageTicket(): ?Money
    {
        return $this->bookings === 0 ? null : $this->sale->dividedBy($this->bookings, RoundingMode::HALF_UP);
    }

    /** Variación porcentual contra otro período ("+12.5" / "-3.0"); null si el anterior es cero. */
    public function growthAgainst(self $previous): ?string
    {
        if ($previous->sale->isZero()) {
            return null;
        }

        return $this->ratio($this->sale->minus($previous->sale), $previous->sale);
    }

    private function ratio(Money $numerator, Money $denominator): string
    {
        return (string) BigDecimal::of($numerator->getMinorAmount())
            ->multipliedBy(self::PERCENT)
            ->dividedBy($denominator->getMinorAmount(), self::RATE_SCALE, RoundingMode::HALF_UP);
    }
}
