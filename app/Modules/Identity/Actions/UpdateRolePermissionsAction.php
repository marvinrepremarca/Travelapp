<?php

declare(strict_types=1);

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\RolePermissionsViolation;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * El rol con más poder (Gerente de la agencia) administra los permisos de los demás roles. Su propio rol no se
 * edita (siempre tiene todo: nadie deja a la agencia sin administrador) y el permiso de administrar roles no se
 * reparte (nadie se otorga más poder a sí mismo). Cada cambio queda en la auditoría.
 */
final readonly class UpdateRolePermissionsAction
{
    private const GUARD = 'web';

    public function __construct(private PermissionRegistrar $registrar) {}

    /** @param list<Permission> $permissions */
    public function execute(User $actor, Role $role, array $permissions): void
    {
        if ($role === Role::AgencyOwner) {
            throw RolePermissionsViolation::roleLocked($role);
        }

        if (in_array(Permission::RolesManage, $permissions, true)) {
            throw RolePermissionsViolation::permissionReserved();
        }

        $model = RoleModel::findByName($role->value, self::GUARD);
        $before = $model->permissions()->pluck('name')->all();
        $after = array_values(array_unique(array_map(static fn(Permission $permission): string => $permission->value, $permissions)));
        sort($after);

        DB::transaction(static function () use ($model, $after): void {
            $model->syncPermissions($after);
        });
        $this->registrar->forgetCachedPermissions();

        activity(AuditLogName::Identity->value)
            ->causedBy($actor)
            ->event('role_permissions_updated')
            ->withProperties([
                'role' => $role->value,
                'granted' => array_values(array_diff($after, $before)),
                'revoked' => array_values(array_diff($before, $after)),
            ])
            ->log(__('identity.role_permissions.audit', ['role' => $role->label()]));
    }
}
