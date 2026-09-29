<?php

declare(strict_types=1);

namespace App\Modules\Organization\Services;

use App\Modules\Organization\Data\Holiday;
use App\Modules\Organization\Enums\HolidaySource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Festivos nacionales de Colombia para cualquier año:
 * fijos, trasladables al lunes siguiente (Ley 51 de 1983, "Ley Emiliani") y los que dependen de la Pascua.
 */
final class ColombianHolidayCalculator
{
    private const DATE_PATTERN = '%04d-%02d-%02d';

    /** Fecha fija, no se traslada. [mes, día, clave] */
    private const FIXED = [
        [1, 1, 'new_year'],
        [5, 1, 'labour_day'],
        [7, 20, 'independence_day'],
        [8, 7, 'battle_of_boyaca'],
        [12, 8, 'immaculate_conception'],
        [12, 25, 'christmas'],
    ];

    /** Se trasladan al lunes siguiente si no caen en lunes. [mes, día, clave] */
    private const MOVABLE_TO_MONDAY = [
        [1, 6, 'epiphany'],
        [3, 19, 'saint_joseph'],
        [6, 29, 'saint_peter_and_paul'],
        [8, 15, 'assumption'],
        [10, 12, 'columbus_day'],
        [11, 1, 'all_saints'],
        [11, 11, 'independence_of_cartagena'],
    ];

    /** Días respecto al domingo de Pascua, sin traslado. */
    private const EASTER_FIXED_OFFSETS = [
        'holy_thursday' => -3,
        'good_friday' => -2,
    ];

    /** Días respecto al domingo de Pascua, ya trasladados al lunes. */
    private const EASTER_MONDAY_OFFSETS = [
        'ascension' => 43,
        'corpus_christi' => 64,
        'sacred_heart' => 71,
    ];

    /** @return list<Holiday> ordenados por fecha */
    public function forYear(int $year): array
    {
        $holidays = [];

        foreach (self::FIXED as [$month, $day, $key]) {
            $holidays[] = $this->holiday($this->date($year, $month, $day), $key);
        }

        foreach (self::MOVABLE_TO_MONDAY as [$month, $day, $key]) {
            $holidays[] = $this->holiday($this->nextMondayIfNeeded($this->date($year, $month, $day)), $key);
        }

        $easter = $this->easterSunday($year);

        foreach ([...self::EASTER_FIXED_OFFSETS, ...self::EASTER_MONDAY_OFFSETS] as $key => $offset) {
            $holidays[] = $this->holiday($easter->addDays($offset), $key);
        }

        usort($holidays, static fn(Holiday $a, Holiday $b): int => $a->date <=> $b->date);

        return $holidays;
    }

    /** Domingo de Pascua (algoritmo anónimo gregoriano, Meeus/Jones/Butcher). */
    public function easterSunday(int $year): CarbonImmutable
    {
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return $this->date($year, $month, $day);
    }

    private function date(int $year, int $month, int $day): CarbonImmutable
    {
        return CarbonImmutable::parse(sprintf(self::DATE_PATTERN, $year, $month, $day));
    }

    private function nextMondayIfNeeded(CarbonImmutable $date): CarbonImmutable
    {
        return $date->dayOfWeek === CarbonInterface::MONDAY ? $date : $date->next(CarbonInterface::MONDAY);
    }

    private function holiday(CarbonImmutable $date, string $key): Holiday
    {
        return new Holiday($date->startOfDay(), __("organization.holidays.{$key}"), HolidaySource::National);
    }
}
