<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Contracts\HolidayCalendar;
use App\Modules\Organization\Data\Holiday;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Organization\Enums\HolidaySource;
use App\Modules\Organization\Models\HolidayAdjustment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as Cache;
use InvalidArgumentException;

/** Festivos nacionales calculados + ajustes de la agencia, cacheados por año. */
final readonly class AgencyHolidayCalendar implements HolidayCalendar
{
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(
        private ColombianHolidayCalculator $national,
        private Cache $cache,
    ) {}

    public static function cacheKey(int $year): string
    {
        return "organization:holidays:{$year}";
    }

    /** @return list<Holiday> */
    public function holidaysIn(int $year): array
    {
        /** @var list<Holiday> */
        return $this->cache->rememberForever(self::cacheKey($year), fn(): array => $this->build($year));
    }

    public function isHoliday(CarbonImmutable $date): bool
    {
        $day = $date->format(self::DATE_FORMAT);

        foreach ($this->holidaysIn($date->year) as $holiday) {
            if ($holiday->date->format(self::DATE_FORMAT) === $day) {
                return true;
            }
        }

        return false;
    }

    public function isBusinessDay(CarbonImmutable $date): bool
    {
        return $date->isWeekday() && ! $this->isHoliday($date);
    }

    public function addBusinessDays(CarbonImmutable $date, int $days): CarbonImmutable
    {
        if ($days < 0) {
            throw new InvalidArgumentException(__('organization.errors.business_days_negative'));
        }

        $current = $date;

        for ($added = 0; $added < $days;) {
            $current = $current->addDay();

            if ($this->isBusinessDay($current)) {
                $added++;
            }
        }

        return $current;
    }

    /** @return list<Holiday> */
    private function build(int $year): array
    {
        $adjustments = HolidayAdjustment::query()->whereYear('date', $year)->get();

        $removed = $adjustments
            ->filter(static fn(HolidayAdjustment $adjustment): bool => $adjustment->type === HolidayAdjustmentType::Remove)
            ->map(static fn(HolidayAdjustment $adjustment): string => $adjustment->date->format(self::DATE_FORMAT))
            ->all();

        $holidays = array_values(array_filter(
            $this->national->forYear($year),
            static fn(Holiday $holiday): bool => ! in_array($holiday->date->format(self::DATE_FORMAT), $removed, true),
        ));

        foreach ($adjustments as $adjustment) {
            if ($adjustment->type === HolidayAdjustmentType::Add) {
                $holidays[] = new Holiday($adjustment->date, $adjustment->name, HolidaySource::Agency);
            }
        }

        usort($holidays, static fn(Holiday $a, Holiday $b): int => $a->date <=> $b->date);

        return $holidays;
    }
}
