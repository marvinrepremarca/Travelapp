<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Providers;

use App\Modules\Shared\Routing\PathPrefix;
use App\Modules\Suppliers\Contracts\SupplierDirectory;
use App\Modules\Suppliers\Livewire\SupplierForm;
use App\Modules\Suppliers\Livewire\SupplierShow;
use App\Modules\Suppliers\Livewire\SuppliersIndex;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Policies\SupplierPolicy;
use App\Modules\Suppliers\Services\EloquentSupplierDirectory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class SuppliersServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        SupplierDirectory::class => EloquentSupplierDirectory::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'suppliers');

        Livewire::component('suppliers.index', SuppliersIndex::class);
        Livewire::component('suppliers.form', SupplierForm::class);
        Livewire::component('suppliers.show', SupplierShow::class);

        Gate::policy(Supplier::class, SupplierPolicy::class);
    }
}
