<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Data;

use App\Modules\Workflow\Enums\ApprovalType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

final readonly class ApprovalRequestData
{
    /** @param array<string, scalar|null> $context Datos para decidir (monto, porcentaje…), sin datos personales. */
    public function __construct(
        public ApprovalType $type,
        public Model $subject,
        public string $summary,
        public ?string $justification = null,
        public array $context = [],
        public ?CarbonImmutable $expiresAt = null,
    ) {}
}
