<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Contracts\TaskScheduler;
use App\Modules\Workflow\Data\TaskData;
use App\Modules\Workflow\Enums\TaskPriority;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** Crea la tarea del responsable con vencimiento y recordatorio (los recordatorios los envía Workflow). */
final readonly class ComplianceTasks
{
    public function __construct(private TaskScheduler $tasks) {}

    public function schedule(string $title, int $assigneeId, CarbonImmutable $dueOn, int $alertDays, Model $subject, User $actor, TaskPriority $priority = TaskPriority::High): void
    {
        $timezone = config()->string('travel.agency.timezone');
        $due = CarbonImmutable::parse($dueOn->toDateString(), $timezone)->endOfDay();
        $remind = CarbonImmutable::parse($dueOn->toDateString(), $timezone)->subDays($alertDays)->startOfDay();
        // Si el aviso ya pasó, se recuerda de inmediato; si también venció, la tarea queda sin recordatorio.
        $remindAt = $remind->isPast() ? CarbonImmutable::now() : $remind;
        $remindAt = $remindAt->gt($due) ? null : $remindAt;

        $this->tasks->schedule(new TaskData(
            title: $title,
            assigneeId: $assigneeId,
            priority: $priority,
            dueAt: $due->utc(),
            remindAt: $remindAt?->utc(),
            subject: $subject,
        ), $actor);
    }
}
