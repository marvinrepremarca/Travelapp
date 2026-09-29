<?php

declare(strict_types=1);

namespace App\Modules\Shared\Exceptions;

final class InvalidPassengerMix extends BusinessRuleException
{
    public static function noAdults(): self
    {
        return new self(__('shared.errors.passenger_mix_no_adults'));
    }

    public static function tooManyInfants(int $infants, int $adults): self
    {
        return new self(__('shared.errors.passenger_mix_too_many_infants', ['infants' => $infants, 'adults' => $adults]));
    }

    public static function negativeCount(): self
    {
        return new self(__('shared.errors.passenger_mix_negative'));
    }

    public function errorCode(): string
    {
        return 'invalid_passenger_mix';
    }
}
