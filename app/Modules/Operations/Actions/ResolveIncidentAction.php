<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Operations\Exceptions\OperationsRuleViolation;
use App\Modules\Operations\Models\DepartureIncident;
use Carbon\CarbonImmutable;

final readonly class ResolveIncidentAction
{
    public function execute(User $actor, DepartureIncident $incident, string $resolution): void
    {
        throw_unless($incident->isOpen(), OperationsRuleViolation::incidentAlreadyResolved());

        $incident->resolution = $resolution;
        $incident->resolved_by = $actor->id;
        $incident->resolved_at = CarbonImmutable::now();
        $incident->save();
    }
}
