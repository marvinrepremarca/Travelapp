<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class InvalidExchangeRate extends BusinessRuleException
{
    public static function make(): self
    {
        return new self(__('pricing.errors.invalid_rate'));
    }

    public function errorCode(): string
    {
        return 'invalid_exchange_rate';
    }
}
