<?php

declare(strict_types=1);

namespace App\Modules\Shared\Support;

/** Enmascarado por defecto de datos sensibles en la interfaz: AB•••••23. */
final class Mask
{
    private const VISIBLE_START = 2;

    private const VISIBLE_END = 2;

    private const MASK_CHAR = '•';

    private const MIN_MASKED = 3;

    public static function value(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $length = mb_strlen($value);

        if ($length <= self::VISIBLE_START + self::VISIBLE_END) {
            return str_repeat(self::MASK_CHAR, max($length, self::MIN_MASKED));
        }

        return mb_substr($value, 0, self::VISIBLE_START)
            . str_repeat(self::MASK_CHAR, $length - self::VISIBLE_START - self::VISIBLE_END)
            . mb_substr($value, -self::VISIBLE_END);
    }
}
