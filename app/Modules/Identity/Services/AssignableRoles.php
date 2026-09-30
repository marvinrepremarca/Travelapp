<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;

/**
 * Evita la escalada de privilegios: solo se puede asignar un rol
 * cuyos permisos ya tenga quien lo asigna.
 */
final class AssignableRoles
{
    /** @return list<Role> */
    public function for(User $actor): array
    {
        return array_values(array_filter(Role::cases(), fn(Role $role): bool => $this->canAssign($actor, $role)));
    }

    public function canAssign(User $actor, Role $role): bool
    {
        if (! $actor->can(Permission::UsersManage->value)) {
            return false;
        }

        foreach ($role->permissions() as $permission) {
            if (! $actor->can($permission->value)) {
                return false;
            }
        }

        return true;
    }
}
