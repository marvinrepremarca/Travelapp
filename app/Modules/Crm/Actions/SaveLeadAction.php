<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Data\LeadData;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Exceptions\LeadRuleViolation;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Crea (responsable = quien lo registra) o actualiza los datos de un lead. */
final class SaveLeadAction
{
    public function execute(LeadData $data, User $actor, ?Lead $lead = null): Lead
    {
        if (($data->email === null || $data->email === '') && ($data->phone === null || $data->phone === '')) {
            throw LeadRuleViolation::contactRequired();
        }

        $isNew = ! $lead instanceof Lead;
        $lead ??= new Lead();
        $lead->fill([
            'contact_name' => $data->contactName,
            'email' => $data->email === null ? null : mb_strtolower($data->email),
            'phone' => $data->phone,
            'channel' => $data->channel,
            'destination' => $data->destination,
            'travel_start' => $data->travelStart?->toDateString(),
            'travel_end' => $data->travelEnd?->toDateString(),
            'travelers_count' => $data->travelersCount,
            'notes' => $data->notes,
        ]);

        if ($isNew) {
            $lead->status = LeadStatus::New;
            $lead->status_changed_at = CarbonImmutable::now();
            $lead->owner_id = $actor->id;
            $lead->branch_id = $actor->branch_id;
        }

        $lead->save();

        return $lead;
    }
}
