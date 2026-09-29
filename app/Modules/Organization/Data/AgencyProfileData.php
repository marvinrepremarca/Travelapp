<?php

declare(strict_types=1);

namespace App\Modules\Organization\Data;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

final readonly class AgencyProfileData
{
    public function __construct(
        public string $legalName,
        public string $tradeName,
        public string $nit,
        public string $rntNumber,
        public CarbonImmutable $rntExpiresOn,
        public string $address,
        public string $city,
        public string $phone,
        public string $email,
        public ?string $website,
        public ?string $brandPrimaryColor,
        public ?string $brandAccentColor,
        public ?UploadedFile $logo = null,
    ) {}
}
