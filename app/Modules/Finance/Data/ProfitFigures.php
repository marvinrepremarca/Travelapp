<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/**
 * Cifras de rentabilidad en una moneda. El margen y la utilidad son derivados, nunca ingresados.
 * Utilidad = margen (venta − costo) + comisión esperada de proveedores + penalidades cobradas.
 */
final readonly class ProfitFigures
{
    private const PERCENT = 100;

    private const RATE_SCALE = 2;

    public function __construct(
        public Money $sale,
        public Money $cost,
        public Money $commission,
        public Money $penalties,
    ) {}

    public static function zero(string $currency): self
    {
        return new self(Money::zero($currency), Money::zero($currency), Money::zero($currency), Money::zero($currency));
    }

    public function plus(self $other): self
    {
        return new self(
            $this->sale->plus($other->sale),
            $this->cost->plus($other->cost),
            $this->commission->plus($other->commission),
            $this->penalties->plus($other->penalties),
        );
    }

    public function margin(): Money
    {
        return $this->sale->minus($this->cost);
    }

    public function profit(): Money
    {
        return $this->margin()->plus($this->commission)->plus($this->penalties);
    }

    /** Margen sobre la venta en porcentaje ("12.50"); null sin venta. */
    public function marginRate(): ?string
    {
        if ($this->sale->isZero()) {
            return null;
        }

        return (string) BigDecimal::of($this->margin()->getMinorAmount())
            ->multipliedBy(self::PERCENT)
            ->dividedBy($this->sale->getMinorAmount(), self::RATE_SCALE, RoundingMode::HALF_UP);
    }

    public function currency(): string
    {
        return $this->sale->getCurrency()->getCurrencyCode();
    }
}
