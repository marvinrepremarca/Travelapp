<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Enums;

use App\Modules\Shared\Enums\Tone;

/**
 * Estado de un servicio con su proveedor:
 * por solicitar → en espera | confirmado | rechazado; en espera → confirmado | rechazado; confirmado o rechazado → cancelado.
 */
enum BookingItemStatus: string
{
    case Pending = 'pending';
    case OnHold = 'on_hold';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::OnHold, self::Confirmed, self::Rejected, self::Cancelled],
            self::OnHold => [self::Confirmed, self::Rejected, self::Cancelled],
            self::Confirmed => [self::Cancelled],
            // Un rechazado se descarta (cancela) cuando ya se reemplazó o el cliente desiste.
            self::Rejected => [self::Cancelled],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /** Ya no cuenta para el viaje (no se confirma ni se cobra). */
    public function isClosed(): bool
    {
        return $this === self::Rejected || $this === self::Cancelled;
    }

    public function label(): string
    {
        return __("bookings.item_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Neutral,
            self::OnHold => Tone::Warning,
            self::Confirmed => Tone::Success,
            self::Rejected, self::Cancelled => Tone::Danger,
        };
    }
}
