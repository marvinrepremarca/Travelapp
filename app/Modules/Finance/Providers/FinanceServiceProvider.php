<?php

declare(strict_types=1);

namespace App\Modules\Finance\Providers;

use App\Modules\Bookings\Events\BookingItemCancelled;
use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Finance\Listeners\RegisterSupplierPayable;
use App\Modules\Finance\Listeners\VoidSupplierPayable;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class FinanceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'finance');

        Livewire::component('finance.payables', PayablesIndex::class);

        // Las obligaciones con proveedores siguen la vida de los servicios del expediente.
        Event::listen(BookingItemConfirmed::class, RegisterSupplierPayable::class);
        Event::listen(BookingItemCancelled::class, VoidSupplierPayable::class);
    }
}
