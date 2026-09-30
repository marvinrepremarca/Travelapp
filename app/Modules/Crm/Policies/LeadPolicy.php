<?php

declare(strict_types=1);

namespace App\Modules\Crm\Policies;

use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\Response;

final class LeadPolicy
{
    public function update(User $user, Lead $lead): Response
    {
        return $lead->isVisibleTo($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
