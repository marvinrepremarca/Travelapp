<?php

declare(strict_types=1);

namespace App\Modules\Operations\Enums;

use App\Modules\Shared\Enums\Tone;

/** Gravedad de una incidencia en la operación de una salida. */
enum IncidentSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return __("operations.severity.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Low => Tone::Info,
            self::Medium => Tone::Warning,
            self::High => Tone::Danger,
        };
    }
}
