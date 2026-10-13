<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Evita la escalada de privilegios: solo se puede asignar un rol cuyos permisos (los configurados en
 * Roles y permisos, no los de fábrica) ya tenga quien lo asigna.
 */
final class AssignableRoles
{
    private const GUARD = 'web';

    /** @var array<string, array<int, string>>|null permisos configurados por rol, leídos una vez por instancia */
    private ?array $grantedByRole = null;

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

        foreach ($this->grantedTo($role) as $permission) {
            if (! $actor->can($permission)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, string> */
    private function grantedTo(Role $role): array
    {
        $this->grantedByRole ??= RoleModel::query()
            ->where('guard_name', self::GUARD)
            ->with('permissions:id,name')
            ->get()
            ->mapWithKeys(static fn(RoleModel $model): array => [(string) $model->name => $model->permissions->pluck('name')->map(static fn(mixed $name): string => (string) $name)->values()->all()])
            ->all();

        return $this->grantedByRole[$role->value] ?? [];
    }
}
