<?php

declare(strict_types=1);

namespace App\Modules\Organization\Policies;

use App\Modules\Shared\Enums\Permission;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

/** Las sucursales son datos de la agencia: se administran con un permiso, sin alcance por sucursal. */
final class BranchPolicy
{
    public function viewAny(Authenticatable&Authorizable $user): bool
    {
        return $user->can(Permission::BranchesManage->value);
    }

    public function create(Authenticatable&Authorizable $user): bool
    {
        return $user->can(Permission::BranchesManage->value);
    }

    public function update(Authenticatable&Authorizable $user): bool
    {
        return $user->can(Permission::BranchesManage->value);
    }
}
