<?php

declare(strict_types=1);

namespace App\Modules\Operations\Providers;

use App\Modules\Operations\Livewire\DeparturesBoard;
use App\Modules\Operations\Livewire\DepartureShow;
use App\Modules\Operations\Livewire\GuideForm;
use App\Modules\Operations\Livewire\ResourcesIndex;
use App\Modules\Operations\Livewire\VehicleForm;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Operación en destino: salidas, manifiestos, guías y vehículos. */
final class OperationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'operations');

        Livewire::component('operations.departures', DeparturesBoard::class);
        Livewire::component('operations.departure', DepartureShow::class);
        Livewire::component('operations.resources', ResourcesIndex::class);
        Livewire::component('operations.guide-form', GuideForm::class);
        Livewire::component('operations.vehicle-form', VehicleForm::class);
    }
}
