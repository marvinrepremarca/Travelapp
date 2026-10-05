<?php

declare(strict_types=1);

namespace App\Modules\Finance\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class FinanceRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function payablesNotPayable(): self
    {
        return self::make('payables_not_payable', __('finance.errors.payables_not_payable'));
    }

    public static function mixedSettlement(): self
    {
        return self::make('mixed_settlement', __('finance.errors.mixed_settlement'));
    }

    public static function cashSessionAlreadyOpen(): self
    {
        return self::make('cash_session_already_open', __('finance.errors.cash_session_already_open'));
    }

    public static function cashSessionClosed(): self
    {
        return self::make('cash_session_closed', __('finance.errors.cash_session_closed'));
    }

    public static function cashCurrencyMismatch(string $currency): self
    {
        return self::make('cash_currency_mismatch', __('finance.errors.cash_currency_mismatch', ['currency' => $currency]));
    }

    public static function userWithoutBranch(): self
    {
        return self::make('user_without_branch', __('finance.errors.user_without_branch'));
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
