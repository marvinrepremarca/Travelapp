<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Data;

use App\Modules\Compliance\Enums\ComplianceDocumentType;
use Carbon\CarbonImmutable;

final readonly class DocumentData
{
    public function __construct(
        public ComplianceDocumentType $type,
        public string $number,
        public ?string $issuer,
        public ?CarbonImmutable $startsOn,
        public CarbonImmutable $expiresOn,
        public int $responsibleId,
        public ?string $notes,
    ) {}
}
