<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Contracts\LeadProspects;
use App\Modules\Crm\Data\LeadProspect;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;

final class EloquentLeadProspects implements LeadProspects
{
    public function prospect(User $viewer, string $leadUlid): ?LeadProspect
    {
        $lead = Lead::query()->visibleTo($viewer)->where('ulid', $leadUlid)->with('customer:id,ulid')->first();
        if (! $lead instanceof Lead || ! $lead->status->isOpen()) {
            return null;
        }

        return new LeadProspect($lead->ulid, $lead->contact_name, $lead->email, $lead->phone, $lead->customer?->ulid, $lead->quoteTitle(), $lead->channel);
    }
}
