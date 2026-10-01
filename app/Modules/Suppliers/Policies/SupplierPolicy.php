<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;

/** Todos los usuarios internos consultan proveedores (para reservar); solo algunos roles los administran. */
final class SupplierPolicy
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
        return $user->can(Permission::SuppliersManage->value);
    }
}
