<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Data;

use App\Modules\Compliance\Enums\ObligationRecurrence;
use Carbon\CarbonImmutable;

final readonly class ObligationData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public CarbonImmutable $dueOn,
        public ObligationRecurrence $recurrence,
        public int $responsibleId,
    ) {}
}
