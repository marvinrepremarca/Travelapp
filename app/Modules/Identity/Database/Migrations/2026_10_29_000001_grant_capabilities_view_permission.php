<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * La página de inicio muestra las capacidades del sistema solo a quien tiene este permiso: por defecto el
 * Administrador del sistema y el Gerente de la agencia. El resto ve su resumen personal.
 */
return new class extends Migration {
    private const GUARD = 'web';

    private const DEFAULT_ROLES = [Role::SystemAdmin, Role::AgencyOwner];

    public function up(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        PermissionModel::findOrCreate(Permission::CapabilitiesView->value, self::GUARD);

        foreach (self::DEFAULT_ROLES as $role) {
            RoleModel::query()->where('name', $role->value)->where('guard_name', self::GUARD)->first()?->givePermissionTo(Permission::CapabilitiesView->value);
        }

        $registrar->forgetCachedPermissions();
    }

    public function down(): void
    {
        PermissionModel::query()->where('name', Permission::CapabilitiesView->value)->where('guard_name', self::GUARD)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
