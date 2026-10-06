<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

use App\Modules\Shared\Enums\Tone;

/** Solicitud de nota crédito: solicitada → emitida (al aprobarse) | rechazada. */
enum AdjustmentStatus: string
{
    case Requested = 'requested';
    case Issued = 'issued';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("invoicing.adjustment_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Requested => Tone::Warning,
            self::Issued => Tone::Success,
            self::Rejected => Tone::Danger,
        };
    }
}
