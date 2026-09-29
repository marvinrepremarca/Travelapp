<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Canal por el que se origina una venta. */
enum SalesChannel: string
{
    case Branch = 'branch';
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case Online = 'online';
    case Partner = 'partner';
    case Corporate = 'corporate';

    public function label(): string
    {
        return __("shared.sales_channel.{$this->value}");
    }
}
