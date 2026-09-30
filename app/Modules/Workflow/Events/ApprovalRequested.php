<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Events;

use App\Modules\Workflow\Enums\ApprovalType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ApprovalRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public string $approvalUlid,
        public ApprovalType $type,
        public string $subjectType,
        public string $subjectId,
    ) {}
}
