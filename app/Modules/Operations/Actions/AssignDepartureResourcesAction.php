<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Identity\Models\User;
use App\Modules\Operations\Exceptions\OperationsRuleViolation;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Operations\Models\Guide;
use App\Modules\Operations\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Asigna guía y vehículo a una salida. Un mismo guía o vehículo no puede estar en dos salidas cuyos horarios se crucen,
 * y el vehículo debe tener cupo para los pasajeros confirmados (o, si es mayor, el cupo reservado de la salida).
 */
final readonly class AssignDepartureResourcesAction
{
    public function __construct(
        private DepartureSchedule $schedule,
        private DepartureManifests $manifests,
    ) {}

    public function execute(User $actor, string $departureUlid, ?Guide $guide, ?Vehicle $vehicle): DepartureAssignment
    {
        $departure = $this->schedule->find($departureUlid) ?? abort(404);
        if (($guide instanceof Guide && ! $guide->is_active) || ($vehicle instanceof Vehicle && ! $vehicle->is_active)) {
            throw OperationsRuleViolation::inactiveResource();
        }

        $starts = $departure->startsAtLocal()->utc();
        $ends = $departure->endsAtLocal()->utc();
        $passengers = max($departure->reservedSeats, $this->manifests->countsFor([$departure->ulid])[$departure->ulid] ?? 0);
        if ($vehicle instanceof Vehicle && $vehicle->capacity < $passengers) {
            throw OperationsRuleViolation::vehicleTooSmall($vehicle->capacity, $passengers);
        }

        return DB::transaction(function () use ($actor, $departure, $guide, $vehicle, $starts, $ends): DepartureAssignment {
            // Bloquea las asignaciones del recurso para que dos asignaciones simultáneas no choquen.
            $this->assertFree('guide_id', $guide?->id, $departure, $starts, $ends);
            $this->assertFree('vehicle_id', $vehicle?->id, $departure, $starts, $ends);

            $assignment = DepartureAssignment::query()->firstOrNew(['departure_ulid' => $departure->ulid]);
            $assignment->fill([
                'guide_id' => $guide?->id,
                'vehicle_id' => $vehicle?->id,
                'starts_at' => $starts,
                'ends_at' => $ends,
                'assigned_by' => $actor->id,
            ])->save();

            return $assignment;
        });
    }

    private function assertFree(string $column, ?int $resourceId, ScheduledDeparture $departure, CarbonImmutable $starts, CarbonImmutable $ends): void
    {
        if ($resourceId === null) {
            return;
        }

        $clash = DepartureAssignment::query()
            ->where($column, $resourceId)
            ->where('departure_ulid', '!=', $departure->ulid)
            ->where('starts_at', '<', $ends)
            ->where('ends_at', '>', $starts)
            ->lockForUpdate()
            ->first(['departure_ulid']);
        if (! $clash instanceof DepartureAssignment) {
            return;
        }

        $other = $this->schedule->find($clash->departure_ulid);
        $label = $other instanceof ScheduledDeparture ? $other->productName . ' · ' . $other->startsAtLocal()->format('Y-m-d H:i') : $clash->departure_ulid;

        throw $column === 'guide_id' ? OperationsRuleViolation::guideBusy($label) : OperationsRuleViolation::vehicleBusy($label);
    }
}
