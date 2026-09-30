<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Enums\TaskStatus;
use App\Modules\Workflow\Models\Task;
use App\Modules\Workflow\Notifications\TaskReminder;
use Carbon\CarbonImmutable;

/** Envía una sola vez el recordatorio de cada tarea abierta cuya hora de recordatorio ya pasó. */
final class SendDueTaskRemindersAction
{
    private const CHUNK_SIZE = 100;

    public function execute(CarbonImmutable $now): int
    {
        $sent = 0;

        Task::query()
            ->where('status', TaskStatus::Open)
            ->whereNull('reminded_at')
            ->where('remind_at', '<=', $now)
            ->orderBy('id')
            ->chunkById(self::CHUNK_SIZE, function ($tasks) use ($now, &$sent): void {
                $owners = User::query()->whereIn('id', $tasks->pluck('owner_id'))->where('is_active', true)->get()->keyBy('id');

                foreach ($tasks as $task) {
                    $owners->get($task->owner_id)?->notify(new TaskReminder($task));
                    $task->reminded_at = $now;
                    $task->saveQuietly();
                    $sent++;
                }
            });

        return $sent;
    }
}
