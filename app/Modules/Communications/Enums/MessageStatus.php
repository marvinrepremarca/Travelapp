<?php

declare(strict_types=1);

namespace App\Modules\Communications\Enums;

use App\Modules\Shared\Enums\Tone;

enum MessageStatus: string
{
    case Received = 'received';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return __("communications.message_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Received, self::Sent => Tone::Success,
            self::Queued => Tone::Neutral,
            self::Failed => Tone::Danger,
        };
    }
}
