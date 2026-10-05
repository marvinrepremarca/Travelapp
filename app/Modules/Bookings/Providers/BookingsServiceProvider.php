<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Providers;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Livewire\BookingsIndex;
use App\Modules\Bookings\Livewire\ConvertQuote;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Policies\BookingPolicy;
use App\Modules\Bookings\Services\EloquentBookingAccounts;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class BookingsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        BookingAccounts::class => EloquentBookingAccounts::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bookings');

        Livewire::component('bookings.index', BookingsIndex::class);
        Livewire::component('bookings.show', BookingShow::class);
        Livewire::component('bookings.convert-quote', ConvertQuote::class);

        Gate::policy(Booking::class, BookingPolicy::class);
    }
}
