<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Identity\Models\User;
use App\Modules\Operations\Exceptions\OperationsRuleViolation;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Operations\Models\DepartureClosure;
use App\Modules\Operations\Models\DepartureIncident;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Cierra la operación de una salida: solo cuando ya terminó (hora local del destino), tuvo guía asignado y no quedan
 * incidencias abiertas. Asistentes + no presentados = pasajeros confirmados del manifiesto.
 */
final readonly class CloseDepartureAction
{
    public function __construct(private DepartureSchedule $schedule, private DepartureManifests $manifests) {}

    public function execute(User $actor, string $departureUlid, int $noShows, ?string $notes): DepartureClosure
    {
        $departure = $this->schedule->find($departureUlid) ?? abort(404);
        throw_if($departure->endsAtLocal()->isFuture(), OperationsRuleViolation::departureNotFinished());
        throw_unless(DepartureAssignment::query()->where('departure_ulid', $departureUlid)->whereNotNull('guide_id')->exists(), OperationsRuleViolation::closeWithoutGuide());
        throw_if(DepartureIncident::query()->where('departure_ulid', $departureUlid)->whereNull('resolved_at')->exists(), OperationsRuleViolation::openIncidents());

        $passengers = $this->manifests->countsFor([$departureUlid])[$departureUlid] ?? 0;
        throw_if($noShows < 0 || $noShows > $passengers, OperationsRuleViolation::invalidNoShows($passengers));

        return DB::transaction(static function () use ($actor, $departureUlid, $passengers, $noShows, $notes): DepartureClosure {
            throw_if(DepartureClosure::query()->where('departure_ulid', $departureUlid)->lockForUpdate()->exists(), OperationsRuleViolation::departureClosed());

            $closure = new DepartureClosure(['departure_ulid' => $departureUlid, 'attended' => $passengers - $noShows, 'no_shows' => $noShows, 'notes' => $notes]);
            $closure->closed_by = $actor->id;
            $closure->closed_at = CarbonImmutable::now();
            $closure->save();

            return $closure;
        });
    }
}
