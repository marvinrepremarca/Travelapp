<?php

declare(strict_types=1);

namespace App\Modules\Communications\Enums;

/** Quién escribió el mensaje: el cliente, el bot guiado, un asesor o el sistema (avisos automáticos). */
enum MessageAuthor: string
{
    case Customer = 'customer';
    case Bot = 'bot';
    case Agent = 'agent';
    case System = 'system';

    public function label(): string
    {
        return __("communications.author.{$this->value}");
    }
}
