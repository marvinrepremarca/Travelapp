<?php

declare(strict_types=1);

namespace App\Modules\Organization\Enums;

enum HolidayAdjustmentType: string
{
    /** Día no hábil adicional para la agencia. */
    case Add = 'add';

    /** Festivo nacional en el que la agencia sí trabaja. */
    case Remove = 'remove';

    public function label(): string
    {
        return __("organization.holiday_adjustment_type.{$this->value}");
    }
}
