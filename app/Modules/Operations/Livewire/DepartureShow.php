<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Operations\Actions\AssignDepartureResourcesAction;
use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Operations\Models\Guide;
use App\Modules\Operations\Models\Vehicle;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Una salida: manifiesto de pasajeros y asignación de guía y vehículo. */
#[Layout('components.layouts.backoffice')]
final class DepartureShow extends Component
{
    use AuthorizesOperations;

    #[Locked]
    public string $departureUlid = '';

    public string $guide = '';

    public string $vehicle = '';

    public function mount(string $departure, DepartureSchedule $schedule): void
    {
        $this->authorizeOperations();
        abort_unless($schedule->find($departure) instanceof ScheduledDeparture, 404);
        $this->departureUlid = $departure;
        $assignment = DepartureAssignment::query()->where('departure_ulid', $departure)->with(['guide:id,ulid', 'vehicle:id,ulid'])->first();
        $this->guide = (string) $assignment?->guide?->ulid;
        $this->vehicle = (string) $assignment?->vehicle?->ulid;
    }

    public function assign(AssignDepartureResourcesAction $assign): void
    {
        $this->authorizeOperations();
        $this->validate([
            'guide' => ['nullable', 'string', 'exists:guides,ulid'],
            'vehicle' => ['nullable', 'string', 'exists:vehicles,ulid'],
        ], attributes: ['guide' => __('operations.departures.guide'), 'vehicle' => __('operations.departures.vehicle')]);

        try {
            $assign->execute(
                $this->actor(),
                $this->departureUlid,
                $this->guide === '' ? null : Guide::query()->where('ulid', $this->guide)->first(),
                $this->vehicle === '' ? null : Vehicle::query()->where('ulid', $this->vehicle)->first(),
            );
        } catch (BusinessRuleException $violation) {
            $this->addError('assignment', $violation->getMessage());

            return;
        }

        session()->flash('status', __('operations.departures.assigned'));
    }

    public function render(DepartureSchedule $schedule, DepartureManifests $manifests): View
    {
        $departure = $schedule->find($this->departureUlid) ?? abort(404);
        $title = __('operations.departures.show_title', ['product' => $departure->productName, 'date' => $departure->startsAtLocal()->translatedFormat(config()->string('travel.communications.notice_datetime_format'))]);

        return view('operations::livewire.departure-show', [
            'departure' => $departure,
            'passengers' => $manifests->passengersOf($departure->ulid),
            'guides' => Guide::query()->where('is_active', true)->orderBy('name')->pluck('name', 'ulid')->all(),
            'vehicles' => Vehicle::query()->where('is_active', true)->orderBy('plate')->get()->mapWithKeys(static fn(Vehicle $vehicle): array => [$vehicle->ulid => __('operations.departures.vehicle_option', ['plate' => $vehicle->plate, 'description' => $vehicle->description, 'capacity' => $vehicle->capacity])])->all(),
        ])->title($title)->layoutData(['heading' => $title]);
    }
}
