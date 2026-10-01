<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Enums;

use App\Modules\Shared\Enums\Tone;

/** Estado del expediente, derivado de sus ítems (nunca se digita). */
enum BookingStatus: string
{
    /** Hay servicios por solicitar o en espera. */
    case InProgress = 'in_progress';
    /** Todos los servicios vigentes están confirmados. */
    case Confirmed = 'confirmed';
    /** Hay servicios rechazados por el proveedor: se reemplazan o se descartan. */
    case NeedsAttention = 'needs_attention';
    /** Todos los servicios están cancelados. */
    case Cancelled = 'cancelled';

    /**
     * @param  list<BookingItemStatus>  $items
     */
    public static function derive(array $items): self
    {
        $active = array_filter($items, static fn(BookingItemStatus $status): bool => ! $status->isClosed());
        $pending = array_filter($active, static fn(BookingItemStatus $status): bool => $status !== BookingItemStatus::Confirmed);

        return match (true) {
            $pending !== [] => self::InProgress,
            in_array(BookingItemStatus::Rejected, $items, true) => self::NeedsAttention,
            $active === [] => self::Cancelled,
            default => self::Confirmed,
        };
    }

    public function label(): string
    {
        return __("bookings.status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::InProgress => Tone::Info,
            self::Confirmed => Tone::Success,
            self::NeedsAttention => Tone::Warning,
            self::Cancelled => Tone::Danger,
        };
    }
}
