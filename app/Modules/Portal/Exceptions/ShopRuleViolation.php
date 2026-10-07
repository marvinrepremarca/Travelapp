<?php

declare(strict_types=1);

namespace App\Modules\Portal\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class ShopRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function departureUnavailable(): self
    {
        return self::make('departure_unavailable', __('portal.shop.errors.departure_unavailable'));
    }

    public static function notEnoughSeats(int $available): self
    {
        return self::make('not_enough_seats', __('portal.shop.errors.not_enough_seats', ['available' => $available]));
    }

    public static function consentRequired(): self
    {
        return self::make('consent_required', __('portal.shop.errors.consent_required'));
    }

    public static function noAdvisor(): self
    {
        return self::make('no_advisor', __('portal.shop.errors.no_advisor'));
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
