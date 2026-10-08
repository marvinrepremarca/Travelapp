<?php

declare(strict_types=1);

namespace App\Modules\Customers\Services;

use Illuminate\Contracts\Config\Repository as Config;
use RuntimeException;

/**
 * Huella HMAC de datos cifrados para buscarlos por coincidencia exacta sin descifrar.
 * Usa una clave dedicada (TRAVEL_PII_HASH_KEY), distinta de APP_KEY.
 */
final readonly class PersonalDataHasher
{
    private const ALGORITHM = 'sha256';

    public function __construct(private Config $config) {}

    public function hash(string $scope, string $normalizedValue): string
    {
        $key = $this->config->string('travel.privacy.hash_key');

        if ($key === '') {
            throw new RuntimeException(__('customers.errors.hash_key_missing'));
        }

        return hash_hmac(self::ALGORITHM, $scope . '|' . $normalizedValue, $key);
    }
}
