<?php

declare(strict_types=1);

namespace App\Modules\Organization\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;
use Carbon\CarbonImmutable;

final class InvalidHolidayAdjustment extends BusinessRuleException
{
    public static function notANationalHoliday(CarbonImmutable $date): self
    {
        return new self(__('organization.errors.not_a_national_holiday', ['date' => $date->toDateString()]));
    }

    public static function alreadyANationalHoliday(CarbonImmutable $date): self
    {
        return new self(__('organization.errors.already_a_national_holiday', ['date' => $date->toDateString()]));
    }

    public function errorCode(): string
    {
        return 'invalid_holiday_adjustment';
    }
}
