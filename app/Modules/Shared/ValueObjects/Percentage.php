<?php

declare(strict_types=1);

namespace App\Modules\Shared\ValueObjects;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

/** Porcentaje exacto en puntos básicos (1 % = 100 pb). Nunca float. */
final readonly class Percentage
{
    private const BASIS_POINTS_PER_PERCENT = 100;

    private const BASIS_POINTS_PER_UNIT = 10000;

    private const FRACTION_SCALE = 4;

    private function __construct(public int $basisPoints) {}

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /** Recibe texto decimal ("19", "12.5") para no pasar nunca por float. */
    public static function fromString(string $percent): self
    {
        return new self(BigDecimal::of($percent)
            ->multipliedBy(self::BASIS_POINTS_PER_PERCENT)
            ->toScale(0, RoundingMode::HALF_UP)
            ->toInt());
    }

    public function toFraction(): BigDecimal
    {
        return BigDecimal::of($this->basisPoints)->dividedBy(self::BASIS_POINTS_PER_UNIT, self::FRACTION_SCALE);
    }

    /** Aplica el porcentaje a un importe, redondeando HALF_UP a la escala de la moneda. */
    public function applyTo(Money $amount): Money
    {
        return $amount->multipliedBy($this->toFraction(), RoundingMode::HALF_UP);
    }

    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }
}
