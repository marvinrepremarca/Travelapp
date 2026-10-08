<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Events;

use App\Modules\Shared\IntegrationEvents\IntegrationEvent;
use App\Modules\Shared\IntegrationEvents\PublishesToOutbox;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/** El módulo dueño del registro escucha este evento y ejecuta (o descarta) la acción aprobada. */
final readonly class ApprovalResolved implements IntegrationEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use PublishesToOutbox;

    public const NAME = 'workflow.approval_resolved';

    public function __construct(
        public string $approvalUlid,
        public ApprovalType $type,
        public ApprovalStatus $status,
        public string $subjectType,
        public string $subjectId,
    ) {}

    public static function eventName(): string
    {
        return self::NAME;
    }
}
