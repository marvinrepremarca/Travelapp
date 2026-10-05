<?php

declare(strict_types=1);

namespace App\Modules\Search\Providers;

use App\Modules\Search\Contracts\SupplierGateway;
use App\Modules\Search\Livewire\FlightSearch;
use App\Modules\Search\Livewire\HotelSearch;
use App\Modules\Search\Services\TaggedSupplierGateway;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class SearchServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        SupplierGateway::class => TaggedSupplierGateway::class,
    ];

    public function boot(): void
    {
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'search');

        Livewire::component('search.flights', FlightSearch::class);
        Livewire::component('search.hotels', HotelSearch::class);
    }
}
