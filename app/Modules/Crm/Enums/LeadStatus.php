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

    /**
     * Pasos que una persona puede dar. "Cotizado" lo pone solo el envío de una cotización; con Cotizaciones
     * apagada no hay cotización posible, así que un lead contactado se puede ganar directamente (ADR-0007).
     *
     * @return list<self>
     */
    public function manualTransitions(bool $quotingEnabled): array
    {
        $next = array_values(array_filter($this->allowedTransitions(), static fn(self $status): bool => $status !== self::Quoted));

        return $this === self::Contacted && ! $quotingEnabled ? [self::Won, ...$next] : $next;
    }

    /** Estados desde los que el envío de una cotización marca el lead como cotizado. */
    public function canBeQuoted(): bool
    {
        return in_array($this, [self::New, self::Contacted], true);
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
