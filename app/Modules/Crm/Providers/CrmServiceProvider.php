<?php

declare(strict_types=1);

namespace App\Modules\Crm\Providers;

use App\Modules\Crm\Contracts\CustomerContacts;
use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Crm\Livewire\CustomerForm;
use App\Modules\Crm\Livewire\CustomerShow;
use App\Modules\Crm\Livewire\CustomersIndex;
use App\Modules\Crm\Livewire\LeadForm;
use App\Modules\Crm\Livewire\LeadsBoard;
use App\Modules\Crm\Livewire\LeadShow;
use App\Modules\Crm\Livewire\TravelerForm;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Crm\Policies\CustomerPolicy;
use App\Modules\Crm\Policies\LeadPolicy;
use App\Modules\Crm\Services\ActionLeadIntake;
use App\Modules\Crm\Services\EloquentCustomerContacts;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CrmServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        LeadIntake::class => ActionLeadIntake::class,
        CustomerContacts::class => EloquentCustomerContacts::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'crm');

        Livewire::component('crm.customers-index', CustomersIndex::class);
        Livewire::component('crm.customer-form', CustomerForm::class);
        Livewire::component('crm.customer-show', CustomerShow::class);
        Livewire::component('crm.traveler-form', TravelerForm::class);
        Livewire::component('crm.leads-board', LeadsBoard::class);
        Livewire::component('crm.lead-form', LeadForm::class);
        Livewire::component('crm.lead-show', LeadShow::class);

        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
    }
}
