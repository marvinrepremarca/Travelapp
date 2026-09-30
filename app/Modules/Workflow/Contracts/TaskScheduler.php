<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Contracts;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Data\TaskData;
use App\Modules\Workflow\Models\Task;

/** Para que otros módulos creen tareas (p. ej. "confirmar reserva bajo petición") sin conocer Workflow por dentro. */
interface TaskScheduler
{
    public function schedule(TaskData $data, User $createdBy): Task;
}
