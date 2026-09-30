<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Exceptions\InvalidTask;
use App\Modules\Workflow\Models\Task;
use Carbon\CarbonImmutable;

final class ChangeTaskStatusAction
{
    public function execute(Task $task, TaskStatus $next): Task
    {
        if (! $task->status->canTransitionTo($next)) {
            throw InvalidTask::transition($task->status, $next);
        }

        $task->status = $next;
        $task->completed_at = $next === TaskStatus::Done ? CarbonImmutable::now() : null;
        $task->save();

        return $task;
    }
}
