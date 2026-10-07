<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Data\ScheduledDeparture;
use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\DepartureAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Salidas de los próximos días con ocupación, guía y vehículo asignados y alertas de lo que falta. */
#[Layout('components.layouts.backoffice')]
final class DeparturesBoard extends Component
{
    use AuthorizesOperations;

    private const DATE_FORMAT = 'Y-m-d';

    #[Url(except: '')]
    public string $from = '';

    public function mount(): void
    {
        $this->authorizeOperations();
    }

    public function render(DepartureSchedule $schedule, DepartureManifests $manifests): View
    {
        $timezone = config()->string('travel.agency.timezone');
        $parsed = $this->from === '' ? null : CarbonImmutable::createFromFormat('!' . self::DATE_FORMAT, $this->from, $timezone);
        $from = $parsed instanceof CarbonImmutable ? $parsed : CarbonImmutable::now($timezone)->startOfDay();
        $departures = $schedule->between($from, $from->addDays(config()->integer('travel.operations.board_days')));
        $ulids = array_map(static fn(ScheduledDeparture $departure): string => $departure->ulid, $departures);

        return view('operations::livewire.departures-board', [
            'departures' => $departures,
            'passengers' => $manifests->countsFor($ulids),
            'assignments' => DepartureAssignment::query()->whereIn('departure_ulid', $ulids)->with(['guide:id,name', 'vehicle:id,plate,capacity'])->get()->keyBy('departure_ulid'),
            'fromDate' => $from,
        ])->title(__('operations.departures.title'))
            ->layoutData(['heading' => __('operations.departures.title')]);
    }
}
