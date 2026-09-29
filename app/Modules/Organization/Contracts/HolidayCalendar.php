<?php

declare(strict_types=1);

namespace App\Modules\Organization\Contracts;

use App\Modules\Organization\Data\Holiday;
use Carbon\CarbonImmutable;

/** Calendario de días no hábiles de la agencia: festivos nacionales + ajustes propios. */
interface HolidayCalendar
{
    /** @return list<Holiday> */
    public function holidaysIn(int $year): array;

    public function isHoliday(CarbonImmutable $date): bool;

    /** Día hábil = lunes a viernes que no es festivo. */
    public function isBusinessDay(CarbonImmutable $date): bool;

    /** Suma días hábiles saltando fines de semana y festivos. */
    public function addBusinessDays(CarbonImmutable $date, int $days): CarbonImmutable;
}
