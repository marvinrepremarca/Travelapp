<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Enums;

/** Cómo se recibió la aceptación del cliente. */
enum AcceptanceChannel: string
{
    /** El asesor la registra (teléfono, correo, en persona) con una nota. */
    case Agent = 'agent';
    /** El cliente la aceptó desde el enlace firmado. */
    case CustomerLink = 'customer_link';

    public function label(): string
    {
        return __("quotes.acceptance_channel.{$this->value}");
    }
}
