<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

use App\Modules\Shared\Enums\Tone;

/** Embudo comercial: new → contacted → quoted → won | lost. */
enum LeadStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Quoted = 'quoted';
    case Won = 'won';
    case Lost = 'lost';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Contacted, self::Lost],
            self::Contacted => [self::Quoted, self::Lost],
            self::Quoted => [self::Won, self::Lost],
            self::Lost => [self::Contacted],
            self::Won => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Won, self::Lost], true);
    }

    public function label(): string
    {
        return __("crm.lead_status.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::New => Tone::Info,
            self::Contacted, self::Quoted => Tone::Warning,
            self::Won => Tone::Success,
            self::Lost => Tone::Neutral,
        };
    }
}
