<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Enums;

use App\Modules\Shared\Enums\Tone;

/** Lo que ve el cliente al abrir el enlace de una versión. Solo `Open` permite aceptar. */
enum CustomerLinkState: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Superseded = 'superseded';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function message(): string
    {
        return __("quotes.public.state.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Open => Tone::Info,
            self::Accepted => Tone::Success,
            self::Superseded, self::Expired => Tone::Warning,
            self::Cancelled => Tone::Danger,
        };
    }
}
