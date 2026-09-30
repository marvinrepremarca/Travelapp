<?php

declare(strict_types=1);

namespace App\Modules\Organization\Data;

final readonly class BranchData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $city,
        public ?string $address,
        public ?string $phone,
        public ?string $email,
        public string $timezone,
        public ?int $managerId,
    ) {}
}
