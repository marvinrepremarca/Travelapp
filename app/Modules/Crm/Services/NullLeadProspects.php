<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Contracts\LeadProspects;
use App\Modules\Crm\Data\LeadProspect;
use App\Modules\Identity\Models\User;

/** Comercial apagada: no hay leads; la cotización se dirige a un cliente. */
final class NullLeadProspects implements LeadProspects
{
    public function prospect(User $viewer, string $leadUlid): ?LeadProspect
    {
        return null;
    }
}
