<?php

declare(strict_types=1);

namespace App\Modules\Organization\Enums;

enum SettingType: string
{
    case String = 'string';
    case Integer = 'integer';
    case Boolean = 'boolean';

    public function cast(mixed $value): string|int|bool
    {
        return match ($this) {
            self::Integer => (int) $value,
            self::Boolean => filter_var($value, FILTER_VALIDATE_BOOL),
            self::String => (string) $value,
        };
    }
}
