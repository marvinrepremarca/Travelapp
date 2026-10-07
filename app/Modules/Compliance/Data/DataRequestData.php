<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Data;

use App\Modules\Compliance\Enums\DataRequestType;
use App\Modules\Crm\Enums\ConsentChannel;
use Carbon\CarbonImmutable;

final readonly class DataRequestData
{
    public function __construct(
        public DataRequestType $type,
        public string $requesterName,
        public string $documentNumber,
        public ?string $email,
        public ?string $phone,
        public string $details,
        public ConsentChannel $channel,
        public CarbonImmutable $receivedAt,
    ) {}
}
