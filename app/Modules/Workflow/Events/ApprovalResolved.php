<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Events;

use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** El módulo dueño del registro escucha este evento y ejecuta (o descarta) la acción aprobada. */
final readonly class ApprovalResolved implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $approvalUlid,
        public ApprovalType $type,
        public ApprovalStatus $status,
        public string $subjectType,
        public string $subjectId,
    ) {}
}
