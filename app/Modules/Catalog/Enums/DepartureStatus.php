<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Enums;

use App\Modules\Shared\Enums\Tone;

/** Estado de una salida. Solo las abiertas se venden; cerrar detiene la venta sin tocar los apartados. */
enum DepartureStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return __("catalog.departure_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Open => Tone::Success,
            self::Closed => Tone::Warning,
        };
    }
}
