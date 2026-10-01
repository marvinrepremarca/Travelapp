<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;
use Carbon\CarbonImmutable;

final class ExchangeRateUnavailable extends BusinessRuleException
{
    public static function forPair(string $from, string $to, CarbonImmutable $date): self
    {
        return new self(__('pricing.errors.rate_unavailable', ['from' => $from, 'to' => $to, 'date' => $date->toDateString()]));
    }

    public static function sourceFailed(): self
    {
        return new self(__('pricing.errors.source_failed'));
    }

    public function errorCode(): string
    {
        return 'exchange_rate_unavailable';
    }
}
