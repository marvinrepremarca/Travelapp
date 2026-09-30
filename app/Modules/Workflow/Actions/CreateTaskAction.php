<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Contracts\TaskScheduler;
use App\Modules\Workflow\Data\TaskData;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Exceptions\InvalidTask;
use App\Modules\Workflow\Models\Task;

final class CreateTaskAction implements TaskScheduler
{
    public function execute(TaskData $data, User $createdBy): Task
    {
        $assignee = User::query()->whereKey($data->assigneeId)->where('is_active', true)->first();

        if ($assignee === null) {
            throw InvalidTask::assigneeNotAvailable();
        }

        if ($data->remindAt instanceof \Carbon\CarbonImmutable && $data->dueAt instanceof \Carbon\CarbonImmutable && $data->remindAt->greaterThan($data->dueAt)) {
            throw InvalidTask::reminderAfterDue();
        }

        $task = new Task([
            'title' => $data->title,
            'description' => $data->description,
            'priority' => $data->priority,
            'due_at' => $data->dueAt,
            'remind_at' => $data->remindAt,
            'subject_type' => $data->subject?->getMorphClass(),
            'subject_id' => $data->subject instanceof \Illuminate\Database\Eloquent\Model ? (string) $data->subject->getKey() : null,
        ]);
        $task->status = TaskStatus::Open;
        $task->owner_id = $assignee->id;
        $task->branch_id = $assignee->branch_id;
        $task->created_by = $createdBy->id;
        $task->save();

        return $task;
    }

    public function schedule(TaskData $data, User $createdBy): Task
    {
        return $this->execute($data, $createdBy);
    }
}
