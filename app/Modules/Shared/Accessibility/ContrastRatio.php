<?php

declare(strict_types=1);

namespace App\Modules\Shared\Accessibility;

use InvalidArgumentException;

/**
 * Relación de contraste WCAG 2.x entre dos colores hexadecimales (#rrggbb).
 * Texto normal AA exige >= 4.5.
 */
final class ContrastRatio
{
    public const AA_NORMAL_TEXT = 4.5;

    private const HEX_PATTERN = '/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/i';

    private const CHANNEL_MAX = 255;

    private const LINEAR_THRESHOLD = 0.03928;

    private const LINEAR_DIVISOR = 12.92;

    private const GAMMA_OFFSET = 0.055;

    private const GAMMA_DIVISOR = 1.055;

    private const GAMMA_EXPONENT = 2.4;

    private const LUMINANCE_OFFSET = 0.05;

    /** Coeficientes de luminancia relativa (R, G, B). */
    private const LUMINANCE_WEIGHTS = [0.2126, 0.7152, 0.0722];

    public static function isHex(string $color): bool
    {
        return preg_match(self::HEX_PATTERN, $color) === 1;
    }

    public static function between(string $foreground, string $background): float
    {
        $first = self::luminance($foreground);
        $second = self::luminance($background);

        return (max($first, $second) + self::LUMINANCE_OFFSET) / (min($first, $second) + self::LUMINANCE_OFFSET);
    }

    public static function meetsAa(string $foreground, string $background): bool
    {
        return self::between($foreground, $background) >= self::AA_NORMAL_TEXT;
    }

    private static function luminance(string $color): float
    {
        if (preg_match(self::HEX_PATTERN, $color, $matches) !== 1) {
            throw new InvalidArgumentException(__('shared.errors.invalid_hex_color', ['color' => $color]));
        }

        $luminance = 0.0;

        foreach (self::LUMINANCE_WEIGHTS as $index => $weight) {
            $channel = hexdec($matches[$index + 1]) / self::CHANNEL_MAX;
            $linear = $channel <= self::LINEAR_THRESHOLD
                ? $channel / self::LINEAR_DIVISOR
                : (($channel + self::GAMMA_OFFSET) / self::GAMMA_DIVISOR) ** self::GAMMA_EXPONENT;
            $luminance += $weight * $linear;
        }

        return $luminance;
    }
}
