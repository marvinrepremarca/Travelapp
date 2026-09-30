<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

use App\Modules\Shared\Enums\Tone;

enum SensitiveDataAccessType: string
{
    case Viewed = 'viewed';
    case Exported = 'exported';
    case Downloaded = 'downloaded';

    public function label(): string
    {
        return __("audit.access_types.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Viewed => Tone::Info,
            self::Exported, self::Downloaded => Tone::Warning,
        };
    }
}
