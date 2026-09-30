<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Data;

use App\Modules\Workflow\Enums\TaskPriority;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

final readonly class TaskData
{
    public function __construct(
        public string $title,
        public int $assigneeId,
        public TaskPriority $priority = TaskPriority::Normal,
        public ?string $description = null,
        public ?CarbonImmutable $dueAt = null,
        public ?CarbonImmutable $remindAt = null,
        public ?Model $subject = null,
    ) {}
}
