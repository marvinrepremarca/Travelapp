<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Models\Task;
use Illuminate\Auth\Access\Response;

/** Tareas: cualquier usuario interno gestiona las que estén en su alcance; fuera de alcance → 404. */
final class TaskPolicy
{
    public function update(User $user, Task $task): Response
    {
        return $task->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
