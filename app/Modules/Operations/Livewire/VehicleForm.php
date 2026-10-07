<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Operations\Actions\SaveVehicleAction;
use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Crear o editar un vehículo. */
#[Layout('components.layouts.backoffice')]
final class VehicleForm extends Component
{
    use AuthorizesOperations;

    #[Locked]
    public ?string $vehicleUlid = null;

    public string $plate = '';

    public string $description = '';

    public string $capacity = '';

    public bool $is_active = true;

    public function mount(?Vehicle $vehicle = null): void
    {
        $this->authorizeOperations();
        if ($vehicle?->exists) {
            $this->vehicleUlid = $vehicle->ulid;
            $this->fill(['plate' => $vehicle->plate, 'description' => $vehicle->description, 'capacity' => (string) $vehicle->capacity, 'is_active' => $vehicle->is_active]);
        }
    }

    public function save(SaveVehicleAction $save): void
    {
        $this->authorizeOperations();
        $this->plate = mb_strtoupper(trim($this->plate));
        $validated = $this->validate([
            'plate' => ['required', 'string', 'regex:/^[A-Z0-9-]{4,15}$/', Rule::unique('vehicles', 'plate')->ignore($this->vehicleUlid, 'ulid')],
            'description' => ['required', 'string', 'max:150'],
            'capacity' => ['required', 'integer', 'min:1', 'max:' . config()->integer('travel.operations.max_vehicle_capacity')],
            'is_active' => ['boolean'],
        ], attributes: trans('operations.vehicles.fields'));

        $vehicle = $save->execute([
            'plate' => $validated['plate'],
            'description' => $validated['description'],
            'capacity' => (int) $validated['capacity'],
            'is_active' => (bool) $validated['is_active'],
        ], $this->vehicleUlid === null ? null : Vehicle::query()->where('ulid', $this->vehicleUlid)->firstOrFail());

        session()->flash('status', __('operations.vehicles.saved', ['plate' => $vehicle->plate]));
        $this->redirectRoute('operations.resources', navigate: true);
    }

    public function render(): View
    {
        $title = $this->vehicleUlid === null ? __('operations.vehicles.create') : __('operations.vehicles.edit');

        return view('operations::livewire.vehicle-form')->title($title)->layoutData(['heading' => $title]);
    }
}
