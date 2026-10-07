<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Operations\Actions\AssignDepartureResourcesAction;
use App\Modules\Operations\Actions\CloseDepartureAction;
use App\Modules\Operations\Actions\ReportIncidentAction;
use App\Modules\Operations\Actions\ResolveIncidentAction;
use App\Modules\Operations\Enums\IncidentSeverity;
use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\DepartureAssignment;
use App\Modules\Operations\Models\DepartureClosure;
use App\Modules\Operations\Models\DepartureIncident;
use App\Modules\Operations\Models\Guide;
use App\Modules\Operations\Models\Vehicle;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Una salida: manifiesto, asignación de guía y vehículo, incidencias y cierre de la operación. */
#[Layout('components.layouts.backoffice')]
final class DepartureShow extends Component
{
    use AuthorizesOperations;

    #[Locked]
    public string $departureUlid = '';

    public string $guide = '';

    public string $vehicle = '';

    public string $severity = '';

    public string $description = '';

    /** @var array<string, string> resolución escrita por incidencia (ULID → texto) */
    public array $resolutions = [];

    public string $noShows = '0';

    public string $closingNotes = '';

    public function mount(string $departure, DepartureSchedule $schedule): void
    {
        $this->authorizeOperations();
        abort_unless($schedule->find($departure) instanceof ScheduledDeparture, 404);
        $this->departureUlid = $departure;
        $assignment = DepartureAssignment::query()->where('departure_ulid', $departure)->with(['guide:id,ulid', 'vehicle:id,ulid'])->first();
        $this->guide = (string) $assignment?->guide?->ulid;
        $this->vehicle = (string) $assignment?->vehicle?->ulid;
        $this->severity = IncidentSeverity::Medium->value;
    }

    public function assign(AssignDepartureResourcesAction $assign): void
    {
        $this->authorizeOperations();
        $this->validate([
            'guide' => ['nullable', 'string', 'exists:guides,ulid'],
            'vehicle' => ['nullable', 'string', 'exists:vehicles,ulid'],
        ], attributes: ['guide' => __('operations.departures.guide'), 'vehicle' => __('operations.departures.vehicle')]);

        $this->attempt('assignment', fn(): \App\Modules\Operations\Models\DepartureAssignment => $assign->execute(
            $this->actor(),
            $this->departureUlid,
            $this->guide === '' ? null : Guide::query()->where('ulid', $this->guide)->first(),
            $this->vehicle === '' ? null : Vehicle::query()->where('ulid', $this->vehicle)->first(),
        ), __('operations.departures.assigned'));
    }

    public function reportIncident(ReportIncidentAction $report): void
    {
        $this->authorizeOperations();
        $this->validate([
            'severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'description' => ['required', 'string', 'min:5', 'max:2000'],
        ], attributes: ['severity' => __('operations.incidents.severity'), 'description' => __('operations.incidents.description')]);

        if ($this->attempt('incident', fn(): \App\Modules\Operations\Models\DepartureIncident => $report->execute($this->actor(), $this->departureUlid, IncidentSeverity::from($this->severity), $this->description), __('operations.incidents.reported'))) {
            $this->reset('description');
        }
    }

    public function resolveIncident(string $incident, ResolveIncidentAction $resolve): void
    {
        $this->authorizeOperations();
        $this->validate(
            ["resolutions.{$incident}" => ['required', 'string', 'min:3', 'max:2000']],
            attributes: ["resolutions.{$incident}" => __('operations.incidents.resolution')],
        );
        $model = DepartureIncident::query()->where('departure_ulid', $this->departureUlid)->where('ulid', $incident)->firstOrFail();

        $this->attempt('incident', fn() => $resolve->execute($this->actor(), $model, $this->resolutions[$incident]), __('operations.incidents.resolved'));
    }

    public function close(CloseDepartureAction $close): void
    {
        $this->authorizeOperations();
        $this->validate([
            'noShows' => ['required', 'integer', 'min:0'],
            'closingNotes' => ['nullable', 'string', 'max:2000'],
        ], attributes: ['noShows' => __('operations.closure.no_shows'), 'closingNotes' => __('operations.closure.notes')]);

        $this->attempt('closure', fn(): \App\Modules\Operations\Models\DepartureClosure => $close->execute($this->actor(), $this->departureUlid, (int) $this->noShows, $this->closingNotes === '' ? null : $this->closingNotes), __('operations.closure.closed'));
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
            'incidents' => DepartureIncident::query()->where('departure_ulid', $departure->ulid)->latest('reported_at')->get(),
            'closure' => DepartureClosure::query()->where('departure_ulid', $departure->ulid)->first(),
            'severities' => collect(IncidentSeverity::cases())->mapWithKeys(static fn(IncidentSeverity $severity): array => [$severity->value => $severity->label()])->all(),
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** Ejecuta un caso de uso y muestra su error de negocio en el campo indicado. */
    private function attempt(string $errorKey, Closure $action, string $success): bool
    {
        try {
            $action();
        } catch (BusinessRuleException $violation) {
            $this->addError($errorKey, $violation->getMessage());

            return false;
        }

        session()->flash('status', $success);

        return true;
    }
}
