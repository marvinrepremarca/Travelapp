<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Providers;

use App\Modules\Compliance\Livewire\ComplianceOverview;
use App\Modules\Compliance\Livewire\DataRequestForm;
use App\Modules\Compliance\Livewire\DataRequestShow;
use App\Modules\Compliance\Livewire\DataRequestsIndex;
use App\Modules\Compliance\Livewire\DocumentForm;
use App\Modules\Compliance\Livewire\DocumentsIndex;
use App\Modules\Compliance\Livewire\ObligationForm;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Cumplimiento legal: RNT y pólizas, calendario de obligaciones y solicitudes de titulares (Ley 1581). */
final class ComplianceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'compliance');

        Livewire::component('compliance.overview', ComplianceOverview::class);
        Livewire::component('compliance.documents', DocumentsIndex::class);
        Livewire::component('compliance.document-form', DocumentForm::class);
        Livewire::component('compliance.obligation-form', ObligationForm::class);
        Livewire::component('compliance.requests', DataRequestsIndex::class);
        Livewire::component('compliance.request-form', DataRequestForm::class);
        Livewire::component('compliance.request', DataRequestShow::class);
    }
}
