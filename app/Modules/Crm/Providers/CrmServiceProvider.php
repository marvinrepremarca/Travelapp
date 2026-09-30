<?php

declare(strict_types=1);

namespace App\Modules\Crm\Providers;

use App\Modules\Crm\Livewire\CustomerForm;
use App\Modules\Crm\Livewire\CustomerShow;
use App\Modules\Crm\Livewire\CustomersIndex;
use App\Modules\Crm\Livewire\TravelerForm;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Policies\CustomerPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CrmServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'crm');

        Livewire::component('crm.customers-index', CustomersIndex::class);
        Livewire::component('crm.customer-form', CustomerForm::class);
        Livewire::component('crm.customer-show', CustomerShow::class);
        Livewire::component('crm.traveler-form', TravelerForm::class);

        Gate::policy(Customer::class, CustomerPolicy::class);
    }
}
