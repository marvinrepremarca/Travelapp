<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Tipos de producto que vende la agencia. */
enum ProductType: string
{
    case Flight = 'flight';
    case Hotel = 'hotel';
    case Car = 'car';
    case Transfer = 'transfer';
    case Tour = 'tour';
    case DayTrip = 'day_trip';
    case Activity = 'activity';
    case Insurance = 'insurance';
    case Package = 'package';

    public function label(): string
    {
        return __("shared.product_type.{$this->value}");
    }
}
