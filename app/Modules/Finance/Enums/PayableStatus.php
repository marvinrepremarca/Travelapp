<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

use App\Modules\Shared\Enums\Tone;

/** Pendiente → pagada; o anulada si el servicio se cancela antes de pagarse. */
enum PayableStatus: string
{
    case Open = 'open';
    case Paid = 'paid';
    case Voided = 'voided';

    public function label(): string
    {
        return __("finance.payable_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Open => Tone::Warning,
            self::Paid => Tone::Success,
            self::Voided => Tone::Neutral,
        };
    }
}
