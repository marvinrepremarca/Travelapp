<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\Guide;
use App\Modules\Operations\Models\Vehicle;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Guías y vehículos de la operación. */
#[Layout('components.layouts.backoffice')]
final class ResourcesIndex extends Component
{
    use AuthorizesOperations;

    public function mount(): void
    {
        $this->authorizeOperations();
    }

    public function render(): View
    {
        return view('operations::livewire.resources-index', [
            'guides' => Guide::query()->orderByDesc('is_active')->orderBy('name')->get(),
            'vehicles' => Vehicle::query()->orderByDesc('is_active')->orderBy('plate')->get(),
        ])->title(__('operations.resources.title'))
            ->layoutData(['heading' => __('operations.resources.title')]);
    }
}
