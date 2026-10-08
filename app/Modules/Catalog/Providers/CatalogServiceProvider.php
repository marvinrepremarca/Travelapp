<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Providers;

use App\Modules\Catalog\Contracts\CatalogInventory;
use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Contracts\DepartureSchedule;
use App\Modules\Catalog\Contracts\ShopCatalog;
use App\Modules\Catalog\Livewire\CatalogIndex;
use App\Modules\Catalog\Livewire\ProductForm;
use App\Modules\Catalog\Livewire\ProductShow;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Catalog\Policies\CatalogProductPolicy;
use App\Modules\Catalog\Services\EloquentCatalogInventory;
use App\Modules\Catalog\Services\EloquentCatalogRates;
use App\Modules\Catalog\Services\EloquentDepartureSchedule;
use App\Modules\Catalog\Services\EloquentShopCatalog;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CatalogServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        DepartureSchedule::class => EloquentDepartureSchedule::class,
        ShopCatalog::class => EloquentShopCatalog::class,
        CatalogInventory::class => EloquentCatalogInventory::class,
        CatalogRates::class => EloquentCatalogRates::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::OwnProduct);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'catalog');

        Livewire::component('catalog.index', CatalogIndex::class);
        Livewire::component('catalog.form', ProductForm::class);
        Livewire::component('catalog.show', ProductShow::class);

        Gate::policy(CatalogProduct::class, CatalogProductPolicy::class);
    }
}
