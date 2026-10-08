<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use App\Modules\Workflow\Enums\ApprovalType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ApprovalRequested implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'workflow.approval_requested';

    public function __construct(
        public string $approvalUlid,
        public ApprovalType $type,
        public string $subjectType,
        public string $subjectId,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
