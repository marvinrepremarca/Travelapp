<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

use App\Modules\Shared\Enums\Tone;

enum CashSessionStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return __("finance.cash_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return $this === self::Open ? Tone::Success : Tone::Neutral;
    }
}
