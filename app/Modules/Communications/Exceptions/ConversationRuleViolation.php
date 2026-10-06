<?php

declare(strict_types=1);

namespace App\Modules\Communications\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class ConversationRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function alreadyTaken(): self
    {
        return self::make('already_taken');
    }

    public static function notYours(): self
    {
        return self::make('not_yours');
    }

    public static function closed(): self
    {
        return self::make('closed');
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    private static function make(string $code): self
    {
        $exception = new self(__("communications.errors.{$code}"));
        $exception->stableCode = $code;

        return $exception;
    }
}
