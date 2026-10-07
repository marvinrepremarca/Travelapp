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

    public static function departureClosed(): self
    {
        return self::make('departure_closed', __('operations.errors.departure_closed'));
    }

    public static function departureNotFinished(): self
    {
        return self::make('departure_not_finished', __('operations.errors.departure_not_finished'));
    }

    public static function closeWithoutGuide(): self
    {
        return self::make('close_without_guide', __('operations.errors.close_without_guide'));
    }

    public static function openIncidents(): self
    {
        return self::make('open_incidents', __('operations.errors.open_incidents'));
    }

    public static function incidentAlreadyResolved(): self
    {
        return self::make('incident_resolved', __('operations.errors.incident_resolved'));
    }

    public static function invalidNoShows(int $passengers): self
    {
        return self::make('invalid_no_shows', __('operations.errors.invalid_no_shows', ['passengers' => $passengers]));
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
