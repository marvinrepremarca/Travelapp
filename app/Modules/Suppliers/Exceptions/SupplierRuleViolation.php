<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class SupplierRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function rntRequired(): self
    {
        return self::make('rnt_required', __('suppliers.errors.rnt_required'));
    }

    public static function overlappingCommission(): self
    {
        return self::make('overlapping_commission', __('suppliers.errors.overlapping_commission'));
    }

    public static function invalidValidity(): self
    {
        return self::make('invalid_validity', __('suppliers.errors.invalid_validity'));
    }

    public static function bankDataForbidden(): self
    {
        return self::make('bank_data_forbidden', __('suppliers.errors.bank_data_forbidden'));
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
