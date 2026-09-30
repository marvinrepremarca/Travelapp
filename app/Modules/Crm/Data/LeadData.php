<?php

declare(strict_types=1);

namespace App\Modules\Crm\Data;

use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;

final readonly class LeadData
{
    public function __construct(
        public string $contactName,
        public SalesChannel $channel,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $destination = null,
        public ?CarbonImmutable $travelStart = null,
        public ?CarbonImmutable $travelEnd = null,
        public ?int $travelersCount = null,
        public ?string $notes = null,
    ) {}
}
