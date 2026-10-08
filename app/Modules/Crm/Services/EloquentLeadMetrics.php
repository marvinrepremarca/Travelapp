<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Contracts\LeadMetrics;
use App\Modules\Crm\Data\OpenLead;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

final class EloquentLeadMetrics implements LeadMetrics
{
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int
    {
        return Lead::query()
            ->visibleTo($viewer)
            ->when($ownerId !== null, static fn($query) => $query->where('owner_id', $ownerId))
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $until)
            ->count();
    }

    public function openFor(int $ownerId, int $limit): array
    {
        return array_values(Lead::query()
            ->where('owner_id', $ownerId)
            ->whereIn('status', [LeadStatus::New, LeadStatus::Contacted])
            ->orderBy('status_changed_at')
            ->limit($limit)
            ->get(['id', 'ulid', 'contact_name', 'destination', 'status'])
            ->map(static fn(Lead $lead): OpenLead => new OpenLead($lead->ulid, $lead->contact_name, $lead->destination, $lead->status))
            ->all());
    }
}
