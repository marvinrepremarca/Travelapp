<?php

declare(strict_types=1);

namespace App\Modules\Crm\Providers;

use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Crm\Livewire\LeadForm;
use App\Modules\Crm\Livewire\LeadsBoard;
use App\Modules\Crm\Livewire\LeadShow;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Policies\LeadPolicy;
use App\Modules\Crm\Services\ActionLeadIntake;
use App\Modules\Crm\Services\LeadCustomerOrigins;
use App\Modules\Customers\Contracts\CustomerOrigins;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Capacidad Comercial: embudo de prospectos (ADR-0007). Clientes y viajeros viven en el núcleo (Customers). */
final class CrmServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        LeadIntake::class => ActionLeadIntake::class,
    ];

    /**
     * Los prospectos originan clientes; con Comercial apagada el cliente se registra a mano.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CustomerOrigins::class => LeadCustomerOrigins::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Commercial);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'crm');

        Livewire::component('crm.leads-board', LeadsBoard::class);
        Livewire::component('crm.lead-form', LeadForm::class);
        Livewire::component('crm.lead-show', LeadShow::class);

        Gate::policy(Lead::class, LeadPolicy::class);
    }
}
