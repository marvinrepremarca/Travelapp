<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Enums;

use App\Modules\Shared\Enums\Tone;

/** Situación del proveedor para usarlo en nuevas reservas. */
enum SupplierStanding: string
{
    case Bookable = 'bookable';
    case RntExpiring = 'rnt_expiring';
    case RntMissing = 'rnt_missing';
    case RntExpired = 'rnt_expired';
    case Inactive = 'inactive';

    public function isBookable(): bool
    {
        return in_array($this, [self::Bookable, self::RntExpiring], true);
    }

    public function label(): string
    {
        return __("suppliers.standing.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::Bookable => Tone::Success,
            self::RntExpiring => Tone::Warning,
            self::RntMissing, self::RntExpired => Tone::Danger,
            self::Inactive => Tone::Neutral,
        };
    }
}
