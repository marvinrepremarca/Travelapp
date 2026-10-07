<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Enums;

use App\Modules\Shared\Enums\Tone;

/** Recibida → en trámite → respondida | rechazada. */
enum DataRequestStatus: string
{
    case Received = 'received';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __("compliance.request_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Received => Tone::Warning,
            self::InProgress => Tone::Info,
            self::Answered => Tone::Success,
            self::Rejected => Tone::Neutral,
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Received || $this === self::InProgress;
    }

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Received, self::InProgress];
    }
}
