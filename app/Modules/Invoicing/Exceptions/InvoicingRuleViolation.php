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
