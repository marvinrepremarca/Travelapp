<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Enums;

use App\Modules\Shared\Enums\Tone;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return __("workflow.approval_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Pending => Tone::Warning,
            self::Approved => Tone::Success,
            self::Rejected => Tone::Danger,
            self::Cancelled, self::Expired => Tone::Neutral,
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::Pending;
    }
}
