<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

enum InteractionType: string
{
    case Call = 'call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';
    case Note = 'note';

    public function label(): string
    {
        return __("crm.interaction_type.{$this->value}");
    }
}
