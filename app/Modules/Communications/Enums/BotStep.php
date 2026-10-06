<?php

declare(strict_types=1);

namespace App\Modules\Communications\Enums;

/** Pasos del bot guiado de cotización, en orden. */
enum BotStep: string
{
    case Name = 'name';
    case Destination = 'destination';
    case Departure = 'departure';
    case Return = 'return';
    case Travelers = 'travelers';
    case Done = 'done';

    public function next(): self
    {
        return match ($this) {
            self::Name => self::Destination,
            self::Destination => self::Departure,
            self::Departure => self::Return,
            self::Return => self::Travelers,
            self::Travelers, self::Done => self::Done,
        };
    }

    /** Pregunta que hace el bot en este paso. */
    public function prompt(): string
    {
        return __("communications.bot.ask.{$this->value}");
    }
}
