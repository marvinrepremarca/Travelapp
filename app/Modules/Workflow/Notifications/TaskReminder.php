<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Notifications;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Recordatorio en la aplicación; no incluye datos personales, solo la tarea. */
final class TaskReminder extends Notification
{
    use Queueable;

    public function __construct(public readonly Task $task) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string|null> */
    public function toArray(User $notifiable): array
    {
        return [
            'task_ulid' => $this->task->ulid,
            'title' => $this->task->title,
            'due_at' => $this->task->due_at?->toIso8601String(),
        ];
    }
}
