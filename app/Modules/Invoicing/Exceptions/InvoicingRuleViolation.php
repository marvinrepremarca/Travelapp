<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class InvoicingRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function bookingNotConfirmed(): self
    {
        return self::make('booking_not_confirmed', __('invoicing.errors.booking_not_confirmed'));
    }

    public static function bookingNotPaid(string $balance): self
    {
        return self::make('booking_not_paid', __('invoicing.errors.booking_not_paid', ['balance' => $balance]));
    }

    public static function alreadyInvoiced(): self
    {
        return self::make('already_invoiced', __('invoicing.errors.already_invoiced'));
    }

    public static function nothingToInvoice(): self
    {
        return self::make('nothing_to_invoice', __('invoicing.errors.nothing_to_invoice'));
    }

    public static function notAnInvoice(): self
    {
        return self::make('not_an_invoice', __('invoicing.errors.not_an_invoice'));
    }

    public static function creditExceedsLine(string $line, string $remaining): self
    {
        return self::make('credit_exceeds_line', __('invoicing.errors.credit_exceeds_line', ['line' => $line, 'remaining' => $remaining]));
    }

    public static function nothingToCredit(): self
    {
        return self::make('nothing_to_credit', __('invoicing.errors.nothing_to_credit'));
    }

    public static function invalidCharge(): self
    {
        return self::make('invalid_charge', __('invoicing.errors.invalid_charge'));
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
