<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Identity\Models\User;
use App\Modules\Operations\Enums\IncidentSeverity;
use App\Modules\Operations\Exceptions\OperationsRuleViolation;
use App\Modules\Operations\Models\DepartureClosure;
use App\Modules\Operations\Models\DepartureIncident;
use Carbon\CarbonImmutable;

final readonly class ReportIncidentAction
{
    public function __construct(private DepartureSchedule $schedule) {}

    public function execute(User $actor, string $departureUlid, IncidentSeverity $severity, string $description): DepartureIncident
    {
        $this->schedule->find($departureUlid) ?? abort(404);
        throw_if(DepartureClosure::query()->where('departure_ulid', $departureUlid)->exists(), OperationsRuleViolation::departureClosed());

        $incident = new DepartureIncident(['departure_ulid' => $departureUlid, 'severity' => $severity, 'description' => $description]);
        $incident->reported_by = $actor->id;
        $incident->reported_at = CarbonImmutable::now();
        $incident->save();

        return $incident;
    }
}
