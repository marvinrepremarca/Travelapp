<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Enums;

use App\Modules\Shared\Enums\Tone;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __("workflow.task_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Open => Tone::Info,
            self::Done => Tone::Success,
            self::Cancelled => Tone::Neutral,
        };
    }

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Open => [self::Done, self::Cancelled],
            self::Done, self::Cancelled => [self::Open],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }
}
