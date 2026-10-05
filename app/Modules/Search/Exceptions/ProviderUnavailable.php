<?php

declare(strict_types=1);

namespace App\Modules\Search\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

/** Un proveedor no respondió a tiempo, falló o devolvió algo que no se pudo interpretar. */
final class ProviderUnavailable extends BusinessRuleException
{
    public static function for(string $providerKey): self
    {
        return new self(__('search.errors.provider_unavailable', ['provider' => $providerKey]));
    }

    public function errorCode(): string
    {
        return 'provider_unavailable';
    }
}
