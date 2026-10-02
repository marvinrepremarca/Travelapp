<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Exceptions;

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class BookingRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function alreadyConverted(string $bookingNumber): self
    {
        return self::make('already_converted', __('bookings.errors.already_converted', ['number' => $bookingNumber]));
    }

    public static function invalidTransition(BookingItemStatus $from, BookingItemStatus $to): self
    {
        return self::make('invalid_transition', __('bookings.errors.invalid_transition', ['from' => $from->label(), 'to' => $to->label()]));
    }

    public static function departureRequired(): self
    {
        return self::make('departure_required', __('bookings.errors.departure_required'));
    }

    public static function departureNotAvailable(): self
    {
        return self::make('departure_not_available', __('bookings.errors.departure_not_available'));
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
