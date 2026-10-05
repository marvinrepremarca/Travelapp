<?php

declare(strict_types=1);

namespace App\Modules\Search\Enums;

enum CabinClass: string
{
    case Economy = 'economy';
    case PremiumEconomy = 'premium_economy';
    case Business = 'business';
    case First = 'first';

    public function label(): string
    {
        return __("search.cabin.{$this->value}");
    }
}
