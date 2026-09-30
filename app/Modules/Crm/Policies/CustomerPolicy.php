<?php

declare(strict_types=1);

namespace App\Modules\Crm\Policies;

use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Auth\Access\Response;

/** Clientes por alcance: fuera de alcance → 404. Reasignar exige permiso y alcance mayor que "propio". */
final class CustomerPolicy
{
    public function view(User $user, Customer $customer): Response
    {
        return $customer->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Customer $customer): Response
    {
        return $this->view($user, $customer);
    }

    public function reassign(User $user, Customer $customer): Response
    {
        if (! $customer->isVisibleTo($user)) {
            return Response::denyAsNotFound();
        }

        return $user->can(Permission::CustomersReassign->value) && $user->visibilityScope() !== VisibilityScope::Own
            ? Response::allow()
            : Response::deny();
    }
}
