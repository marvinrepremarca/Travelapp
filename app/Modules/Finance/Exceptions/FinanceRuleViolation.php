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

    public static function statementEmpty(): self
    {
        return self::make('statement_empty', __('finance.errors.statement_empty'));
    }

    public static function statementColumnsMissing(string $columns): self
    {
        return self::make('statement_columns_missing', __('finance.errors.statement_columns_missing', ['columns' => $columns]));
    }

    public static function statementRowInvalid(int $row): self
    {
        return self::make('statement_row_invalid', __('finance.errors.statement_row_invalid', ['row' => $row]));
    }

    public static function statementAlreadyImported(): self
    {
        return self::make('statement_already_imported', __('finance.errors.statement_already_imported'));
    }

    public static function lineNotPending(): self
    {
        return self::make('line_not_pending', __('finance.errors.line_not_pending'));
    }

    public static function matchNotValid(): self
    {
        return self::make('match_not_valid', __('finance.errors.match_not_valid'));
    }

    public static function bankAccountInactive(): self
    {
        return self::make('bank_account_inactive', __('finance.errors.bank_account_inactive'));
    }

    public static function userWithoutBranch(): self
    {
        return self::make('user_without_branch', __('finance.errors.user_without_branch'));
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    public static function revenueAmountInvalid(): self
    {
        return self::make('revenue_amount_invalid', __('finance.errors.revenue_amount_invalid'));
    }

    public static function revenueDateInFuture(): self
    {
        return self::make('revenue_date_in_future', __('finance.errors.revenue_date_in_future'));
    }

    public static function payableAmountInvalid(): self
    {
        return self::make('payable_amount_invalid', __('finance.errors.payable_amount_invalid'));
    }

    private static function make(string $code, string $message): self
    {
        $exception = new self($message);
        $exception->stableCode = $code;

        return $exception;
    }
}
