<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use RuntimeException;

/**
 * Normaliza teléfonos a formato internacional (+57…) y calcula su huella HMAC para buscar sin descifrar.
 * Usa la misma clave dedicada de datos personales (TRAVEL_PII_HASH_KEY).
 */
final class PhoneNumbers
{
    private const ALGORITHM = 'sha256';

    private const SCOPE = 'conversation_phone';

    private const NON_DIGITS = '/\D+/';

    private const INTERNATIONAL_PREFIX = '+';

    public function normalize(string $phone): string
    {
        $digits = (string) preg_replace(self::NON_DIGITS, '', $phone);
        $countryCode = (string) preg_replace(self::NON_DIGITS, '', config()->string('travel.communications.default_country_code'));
        $isInternational = str_starts_with(trim($phone), self::INTERNATIONAL_PREFIX) || str_starts_with($digits, $countryCode);

        return self::INTERNATIONAL_PREFIX . ($isInternational ? $digits : $countryCode . $digits);
    }

    public function hash(string $phone): string
    {
        $key = config()->string('travel.privacy.hash_key');
        if ($key === '') {
            throw new RuntimeException(__('communications.errors.hash_key_missing'));
        }

        return hash_hmac(self::ALGORITHM, self::SCOPE . '|' . $this->normalize($phone), $key);
    }

    /** Para mostrar sin exponer el número completo: +57 ••• ••• 4567. */
    public function mask(string $phone): string
    {
        $normalized = $this->normalize($phone);

        return __('communications.masked_phone', ['last' => substr($normalized, -4)]);
    }
}
