<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Enums;

enum FeeBasis: string
{
    case PerPassenger = 'per_passenger';
    case PerBooking = 'per_booking';

    public function label(): string
    {
        return __("pricing.fee_basis.{$this->value}");
    }
}
