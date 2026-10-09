<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

/** Un ingreso se reconoce una vez y, si el servicio se cancela, se reversa con un movimiento inverso. */
enum RevenueEntryType: string
{
    case Recognition = 'recognition';
    case Reversal = 'reversal';

    public function label(): string
    {
        return __("finance.revenue_entry_type.{$this->value}");
    }
}
