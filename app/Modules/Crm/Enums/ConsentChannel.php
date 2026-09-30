<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

enum ConsentChannel: string
{
    case InPerson = 'in_person';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case Email = 'email';
    case Web = 'web';

    public function label(): string
    {
        return __("crm.consent_channel.{$this->value}");
    }
}
