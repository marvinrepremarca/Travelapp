<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;

/** El catálogo es de toda la agencia: todos los usuarios internos lo consultan para vender; producto lo administra. */
final class CatalogProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user): bool
    {
        return $user->isActive();
    }

    public function manage(User $user): bool
    {
        return $user->can(Permission::CatalogManage->value);
    }
}
