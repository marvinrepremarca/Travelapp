<?php

declare(strict_types=1);

namespace App\Modules\Crm\Enums;

enum LostReason: string
{
    case Price = 'price';
    case Dates = 'dates';
    case Competition = 'competition';
    case NoResponse = 'no_response';
    case Other = 'other';

    public function label(): string
    {
        return __("crm.lost_reason.{$this->value}");
    }
}
