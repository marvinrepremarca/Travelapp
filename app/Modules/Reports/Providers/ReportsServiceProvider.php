<?php

declare(strict_types=1);

namespace App\Modules\Reports\Providers;

use App\Modules\Reports\Livewire\AdvisorDashboard;
use App\Modules\Reports\Livewire\FinanceDashboard;
use App\Modules\Reports\Livewire\ManagementDashboard;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Reportes y tableros: solo lectura sobre los demás módulos. */
final class ReportsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'reports');

        Livewire::component('reports.management', ManagementDashboard::class);
        Livewire::component('reports.advisor', AdvisorDashboard::class);
        Livewire::component('reports.finance', FinanceDashboard::class);
    }
}
