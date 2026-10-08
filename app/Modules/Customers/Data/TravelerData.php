<?php

declare(strict_types=1);

namespace App\Modules\Customers\Data;

use App\Modules\Customers\Enums\Gender;
use Carbon\CarbonImmutable;

final readonly class TravelerData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public Gender $gender,
        public CarbonImmutable $birthDate,
        public string $nationality,
        public ?string $passportNumber = null,
        public ?string $passportCountry = null,
        public ?CarbonImmutable $passportExpiresOn = null,
    ) {}
}
