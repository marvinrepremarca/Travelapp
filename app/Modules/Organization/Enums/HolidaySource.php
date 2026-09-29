<?php

declare(strict_types=1);

namespace App\Modules\Organization\Enums;

use App\Modules\Shared\Enums\Tone;

enum HolidaySource: string
{
    /** Calculado por ley. */
    case National = 'national';

    /** Agregado por la agencia (festivo municipal, cierre). */
    case Agency = 'agency';

    public function label(): string
    {
        return __("organization.holiday_source.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::National => Tone::Info,
            self::Agency => Tone::Neutral,
        };
    }
}
