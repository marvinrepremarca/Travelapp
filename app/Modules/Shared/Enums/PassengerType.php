<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Tipo de pasajero por edad a la fecha del servicio (límites ⚙ en config/travel.php). */
enum PassengerType: string
{
    case Adult = 'adult';
    case Child = 'child';
    case Infant = 'infant';

    public static function forAge(int $ageAtService): self
    {
        return match (true) {
            $ageAtService <= config()->integer('travel.passengers.infant_max_age') => self::Infant,
            $ageAtService <= config()->integer('travel.passengers.child_max_age') => self::Child,
            default => self::Adult,
        };
    }

    public function label(): string
    {
        return __("shared.passenger_type.{$this->value}");
    }
}
