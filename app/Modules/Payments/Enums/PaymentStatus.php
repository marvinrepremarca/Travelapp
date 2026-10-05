<?php

declare(strict_types=1);

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Tone;

/** Pendiente → aprobado | rechazado | vencido (solo links). Un pago aprobado nunca se edita: se corrige con un reembolso. */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return __("payments.status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Approved => Tone::Success,
            self::Rejected, self::Expired => Tone::Danger,
        };
    }
}
