<?php

declare(strict_types=1);

namespace App\Modules\Communications\Enums;

use App\Modules\Shared\Enums\Tone;

/** Conversación de WhatsApp: el bot guiado recoge los datos → espera asesor → con asesor → cerrada. */
enum ConversationStatus: string
{
    case Bot = 'bot';
    case WaitingAgent = 'waiting_agent';
    case WithAgent = 'with_agent';
    case Closed = 'closed';

    public function label(): string
    {
        return __("communications.status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Bot => Tone::Info,
            self::WaitingAgent => Tone::Warning,
            self::WithAgent => Tone::Success,
            self::Closed => Tone::Neutral,
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }
}
