<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Enums;

use App\Modules\Shared\Enums\Tone;

enum TaskPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return __("workflow.task_priority.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Low => Tone::Neutral,
            self::Normal => Tone::Info,
            self::High => Tone::Warning,
            self::Urgent => Tone::Danger,
        };
    }
}
