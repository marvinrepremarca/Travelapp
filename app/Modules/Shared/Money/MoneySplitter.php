<?php

declare(strict_types=1);

namespace App\Modules\Shared\Money;

use Brick\Money\Money;
use InvalidArgumentException;

/**
 * Reparte un importe entre pasajeros o cuotas sin perder centavos (Money::allocate).
 * El residuo va a las primeras partes, así la suma siempre es exactamente el total.
 */
final class MoneySplitter
{
    /** @return list<Money> */
    public static function equally(Money $total, int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException(__('shared.errors.split_parts_positive'));
        }

        return array_values($total->split($parts));
    }

    /**
     * @param  list<int>  $ratios  Proporciones enteras (p. ej. tarifa adulto 100, niño 70).
     * @return list<Money>
     */
    public static function byRatios(Money $total, array $ratios): array
    {
        if ($ratios === [] || array_sum($ratios) < 1 || min($ratios) < 0) {
            throw new InvalidArgumentException(__('shared.errors.split_ratios_invalid'));
        }

        return array_values($total->allocate(...$ratios));
    }
}
