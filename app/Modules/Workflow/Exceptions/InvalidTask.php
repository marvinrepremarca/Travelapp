<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Workflow\Enums\TaskStatus;

final class InvalidTask extends BusinessRuleException
{
    public static function assigneeNotAvailable(): self
    {
        return new self(__('workflow.errors.assignee_not_available'));
    }

    public static function reminderAfterDue(): self
    {
        return new self(__('workflow.errors.reminder_after_due'));
    }

    public static function transition(TaskStatus $from, TaskStatus $to): self
    {
        return new self(__('workflow.errors.task_transition', ['from' => $from->label(), 'to' => $to->label()]));
    }

    public function errorCode(): string
    {
        return 'invalid_task';
    }
}
