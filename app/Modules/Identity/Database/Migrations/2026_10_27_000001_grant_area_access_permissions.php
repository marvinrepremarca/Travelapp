<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Las áreas del menú pasaron a exigir permiso de acceso. Para no cortarle el trabajo a nadie, los roles
 * existentes reciben el acceso que les corresponde por defecto; después el Gerente lo ajusta en Roles y permisos.
 * También crea los permisos nuevos que aún no existan (sin ellos, guardar un rol falla).
 */
return new class extends Migration {
    private const GUARD = 'web';

    public function up(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, self::GUARD);
        }

        foreach (Role::cases() as $role) {
            $model = RoleModel::query()->where('name', $role->value)->where('guard_name', self::GUARD)->first();
            $granted = array_values(array_intersect(
                array_map(static fn(Permission $permission): string => $permission->value, $role->permissions()),
                array_map(static fn(Permission $permission): string => $permission->value, Permission::areaAccess()),
            ));
            if ($model instanceof RoleModel && $granted !== []) {
                $model->givePermissionTo($granted);
            }
        }

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        PermissionModel::query()
            ->whereIn('name', array_map(static fn(Permission $permission): string => $permission->value, Permission::areaAccess()))
            ->where('guard_name', self::GUARD)
            ->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
