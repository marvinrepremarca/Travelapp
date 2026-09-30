<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

/** Dígito de verificación del NIT colombiano (algoritmo DIAN, módulo 11). */
final class NitCheckDigit
{
    /** Pesos DIAN aplicados de derecha a izquierda. */
    private const WEIGHTS = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    private const MODULUS = 11;

    public const MAX_DIGITS = 15;

    public static function for(string $nit): int
    {
        $digits = array_reverse(str_split($nit));
        $sum = 0;

        foreach ($digits as $position => $digit) {
            $sum += (int) $digit * self::WEIGHTS[$position];
        }

        $remainder = $sum % self::MODULUS;

        return $remainder > 1 ? self::MODULUS - $remainder : $remainder;
    }
}
