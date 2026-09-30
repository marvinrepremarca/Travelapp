<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Models\HolidayAdjustment;
use App\Modules\Organization\Services\AgencyHolidayCalendar;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class DeleteHolidayAdjustmentAction
{
    public function __construct(private Cache $cache) {}

    public function execute(HolidayAdjustment $adjustment): void
    {
        $year = $adjustment->date->year;

        $adjustment->delete();

        $this->cache->forget(AgencyHolidayCalendar::cacheKey($year));
    }
}
