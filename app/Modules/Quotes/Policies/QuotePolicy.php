<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Policies;

use App\Modules\Identity\Models\User;
use App\Modules\Quotes\Models\Quote;
use Illuminate\Auth\Access\Response;

/** Cotizaciones por alcance del rol (propias, de la sucursal o todas). Fuera del alcance responde 404. */
final class QuotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function create(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Quote $quote): Response
    {
        return $user->isActive() && $quote->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }

    public function update(User $user, Quote $quote): Response
    {
        return $this->view($user, $quote);
    }
}
