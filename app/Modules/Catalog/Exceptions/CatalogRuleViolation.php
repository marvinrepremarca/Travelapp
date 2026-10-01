<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class CatalogRuleViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function soldOut(int $available): self
    {
        return self::make('sold_out', __('catalog.errors.sold_out', ['available' => $available]));
    }

    public static function departureNotOpen(): self
    {
        return self::make('departure_not_open', __('catalog.errors.departure_not_open'));
    }

    public static function overlappingSeason(): self
    {
        return self::make('overlapping_season', __('catalog.errors.overlapping_season'));
    }

    public static function noSeasonForDate(string $date): self
    {
        return self::make('no_season_for_date', __('catalog.errors.no_season_for_date', ['date' => $date]));
    }

    public static function noRateForPassengerType(string $type): self
    {
        return self::make('no_rate_for_passenger_type', __('catalog.errors.no_rate_for_passenger_type', ['type' => $type]));
    }

    public static function productInactive(): self
    {
        return self::make('product_inactive', __('catalog.errors.product_inactive'));
    }

    public static function notAPackage(): self
    {
        return self::make('not_a_package', __('catalog.errors.not_a_package'));
    }

    public static function nestedPackage(): self
    {
        return self::make('nested_package', __('catalog.errors.nested_package'));
    }

    public static function componentCurrencyMismatch(string $currency): self
    {
        return self::make('component_currency_mismatch', __('catalog.errors.component_currency_mismatch', ['currency' => $currency]));
    }

    public static function duplicatedComponent(): self
    {
        return self::make('duplicated_component', __('catalog.errors.duplicated_component'));
    }

    public static function emptyPackage(): self
    {
        return self::make('empty_package', __('catalog.errors.empty_package'));
    }

    public static function productTypeLocked(): self
    {
        return self::make('product_type_locked', __('catalog.errors.product_type_locked'));
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
