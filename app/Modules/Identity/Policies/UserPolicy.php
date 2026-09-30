<?php

declare(strict_types=1);

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Auth\Access\Response;

/**
 * Usuarios: se ven con `identity.users.view` y se administran con `identity.users.manage`,
 * siempre dentro del alcance de quien consulta. Fuera de alcance responde 404, no 403.
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can(Permission::UsersView->value);
    }

    public function create(User $actor): bool
    {
        return $actor->can(Permission::UsersManage->value);
    }

    public function update(User $actor, User $target): Response
    {
        if (! $target->isVisibleTo($actor)) {
            return Response::denyAsNotFound();
        }

        return $actor->can(Permission::UsersManage->value) ? Response::allow() : Response::deny();
    }
}
