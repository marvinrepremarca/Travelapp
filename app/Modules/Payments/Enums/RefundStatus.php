<?php

declare(strict_types=1);

namespace App\Modules\Payments\Enums;

use App\Modules\Shared\Enums\Tone;

/** Solicitado → aprobado (por finanzas) → pagado; o rechazado. */
enum RefundStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    /** Compromete dinero del cliente: cuenta contra lo que aún se puede reembolsar. */
    public function isCommitted(): bool
    {
        return $this !== self::Rejected;
    }

    public function label(): string
    {
        return __("payments.refund_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Requested => Tone::Warning,
            self::Approved => Tone::Info,
            self::Rejected => Tone::Danger,
            self::Paid => Tone::Success,
        };
    }
}
