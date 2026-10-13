<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Idempotente: se puede ejecutar en cada despliegue sin pisar lo que el Gerente de la agencia configuró en
 * Roles y permisos. Solo un rol nuevo recibe sus permisos por defecto; el Gerente siempre tiene todos y es el
 * único con el permiso de administrar roles.
 */
final class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(PermissionRegistrar $registrar): void
    {
        $registrar->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, self::GUARD);
        }

        foreach (Role::cases() as $role) {
            $this->seedRole($role);
        }

        $registrar->forgetCachedPermissions();
    }

    private function seedRole(Role $role): void
    {
        $model = RoleModel::findOrCreate($role->value, self::GUARD);

        if ($role === Role::AgencyOwner) {
            $model->syncPermissions(array_map(static fn(Permission $permission): string => $permission->value, Permission::cases()));

            return;
        }

        if ($model->wasRecentlyCreated) {
            $model->syncPermissions(array_map(static fn(Permission $permission): string => $permission->value, $role->permissions()));
        }

        if ($model->hasPermissionTo(Permission::RolesManage->value, self::GUARD)) {
            $model->revokePermissionTo(Permission::RolesManage->value);
        }
    }
}
