<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Enums;

use App\Modules\Shared\Enums\Tone;

/**
 * Ciclo de vida: borrador → enviada → aceptada | vencida | cancelada.
 * Enviada o vencida vuelven a borrador para preparar una nueva versión.
 */
enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Sent, self::Cancelled],
            self::Sent => [self::Accepted, self::Expired, self::Draft, self::Cancelled],
            self::Expired => [self::Draft, self::Cancelled],
            self::Accepted, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function label(): string
    {
        return __("quotes.status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Draft => Tone::Neutral,
            self::Sent => Tone::Info,
            self::Accepted => Tone::Success,
            self::Expired => Tone::Warning,
            self::Cancelled => Tone::Danger,
        };
    }
}
