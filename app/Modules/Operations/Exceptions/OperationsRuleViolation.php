<?php

declare(strict_types=1);

namespace App\Modules\Operations\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class OperationsRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function guideBusy(string $other): self
    {
        return self::make('guide_busy', __('operations.errors.guide_busy', ['departure' => $other]));
    }

    public static function vehicleBusy(string $other): self
    {
        return self::make('vehicle_busy', __('operations.errors.vehicle_busy', ['departure' => $other]));
    }

    public static function vehicleTooSmall(int $capacity, int $passengers): self
    {
        return self::make('vehicle_too_small', __('operations.errors.vehicle_too_small', ['capacity' => $capacity, 'passengers' => $passengers]));
    }

    public static function inactiveResource(): self
    {
        return self::make('inactive_resource', __('operations.errors.inactive_resource'));
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
