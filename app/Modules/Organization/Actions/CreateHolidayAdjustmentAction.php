<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Data\HolidayAdjustmentData;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Exceptions\InvalidHolidayAdjustment;
use App\Modules\Organization\Models\HolidayAdjustment;
use App\Modules\Organization\Services\AgencyHolidayCalendar;
use App\Modules\Organization\Services\ColombianHolidayCalculator;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class CreateHolidayAdjustmentAction
{
    public function __construct(
        private ColombianHolidayCalculator $national,
        private Cache $cache,
    ) {}

    public function execute(HolidayAdjustmentData $data): HolidayAdjustment
    {
        $isNational = $this->isNationalHoliday($data);

        if ($data->type === HolidayAdjustmentType::Remove && ! $isNational) {
            throw InvalidHolidayAdjustment::notANationalHoliday($data->date);
        }

        if ($data->type === HolidayAdjustmentType::Add && $isNational) {
            throw InvalidHolidayAdjustment::alreadyANationalHoliday($data->date);
        }

        $adjustment = HolidayAdjustment::query()->create([
            'date' => $data->date->toDateString(),
            'type' => $data->type,
            'name' => $data->name,
        ]);

        $this->cache->forget(AgencyHolidayCalendar::cacheKey($data->date->year));

        return $adjustment;
    }

    private function isNationalHoliday(HolidayAdjustmentData $data): bool
    {
        foreach ($this->national->forYear($data->date->year) as $holiday) {
            if ($holiday->date->isSameDay($data->date)) {
                return true;
            }
        }

        return false;
    }
}
