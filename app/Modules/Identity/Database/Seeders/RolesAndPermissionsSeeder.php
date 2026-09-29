<?php

declare(strict_types=1);

namespace App\Modules\Identity\Database\Seeders;

use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/** Idempotente: se puede ejecutar en cada despliegue. */
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
            RoleModel::findOrCreate($role->value, self::GUARD)
                ->syncPermissions(array_map(static fn(Permission $p): string => $p->value, $role->permissions()));
        }

        $registrar->forgetCachedPermissions();
    }
}
