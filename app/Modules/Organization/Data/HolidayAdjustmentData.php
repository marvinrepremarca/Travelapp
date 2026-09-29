<?php

declare(strict_types=1);

namespace App\Modules\Organization\Data;

use App\Modules\Organization\Enums\HolidayAdjustmentType;
use Carbon\CarbonImmutable;

final readonly class HolidayAdjustmentData
{
    public function __construct(
        public CarbonImmutable $date,
        public HolidayAdjustmentType $type,
        public string $name,
    ) {}
}
