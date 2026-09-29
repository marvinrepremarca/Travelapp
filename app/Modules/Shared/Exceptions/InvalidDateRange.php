<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

use Carbon\CarbonImmutable;

final class InvalidDateRange extends BusinessRuleException
{
    public static function endBeforeStart(CarbonImmutable $start, CarbonImmutable $end): self
    {
        return new self(__('shared.errors.date_range_end_before_start', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]));
    }

    public function errorCode(): string
    {
        return 'invalid_date_range';
    }
}
