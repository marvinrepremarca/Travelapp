<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

use Carbon\CarbonImmutable;

/** Periodicidad de una obligación: al cumplirla se programa la siguiente ocurrencia. */
enum ObligationRecurrence: string
{
    case Once = 'once';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    private const MONTHS_PER_QUARTER = 3;

    public function label(): string
    {
        return __("compliance.recurrence.{$this->value}");
    }

    public function next(CarbonImmutable $dueOn): ?CarbonImmutable
    {
        return match ($this) {
            self::Once => null,
            self::Monthly => $dueOn->addMonthNoOverflow(),
            self::Quarterly => $dueOn->addMonthsNoOverflow(self::MONTHS_PER_QUARTER),
            self::Yearly => $dueOn->addYearNoOverflow(),
        };
    }
}
