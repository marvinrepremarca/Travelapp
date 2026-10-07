<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class ComplianceRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function alreadyCompleted(): self
    {
        return self::make('already_completed', __('compliance.errors.already_completed'));
    }

    public static function requestClosed(): self
    {
        return self::make('request_closed', __('compliance.errors.request_closed'));
    }

    public static function invalidTransition(): self
    {
        return self::make('invalid_transition', __('compliance.errors.invalid_transition'));
    }

    public static function responseRequired(): self
    {
        return self::make('response_required', __('compliance.errors.response_required'));
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    private static function make(string $code, string $message): self
    {
        $exception = new self($message);
        $exception->stableCode = $code;

        return $exception;
    }
}
