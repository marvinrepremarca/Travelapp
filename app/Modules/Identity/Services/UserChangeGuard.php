<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Exceptions\UserManagementViolation;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\VisibilityScope;

/** Reglas comunes al crear o editar usuarios. */
final readonly class UserChangeGuard
{
    public function __construct(private AssignableRoles $assignable) {}

    public function check(UserData $data, User $actor, ?User $target = null): void
    {
        if ($target?->is($actor) && ! $target->hasRole($data->role->value)) {
            throw UserManagementViolation::cannotChangeOwnRole();
        }

        if (! $this->assignable->canAssign($actor, $data->role)) {
            throw UserManagementViolation::roleNotAssignable($data->role);
        }

        // Alcance "propio" o "sucursal" sin sucursal dejaría al usuario sin datos visibles y sin reportes.
        if ($data->branchId === null && $data->scope !== VisibilityScope::All) {
            throw UserManagementViolation::branchRequired();
        }
    }
}
