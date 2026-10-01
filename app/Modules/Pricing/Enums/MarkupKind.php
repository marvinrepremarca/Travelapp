<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Enums;

enum MarkupKind: string
{
    case Percentage = 'percentage';
    case FixedPerPassenger = 'fixed_per_passenger';
    case FixedPerNight = 'fixed_per_night';
    case FixedPerBooking = 'fixed_per_booking';

    public function isFixed(): bool
    {
        return $this !== self::Percentage;
    }

    public function label(): string
    {
        return __("pricing.markup_kind.{$this->value}");
    }
}
