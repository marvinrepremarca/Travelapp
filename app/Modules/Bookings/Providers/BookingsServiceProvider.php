<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Providers;

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Contracts\BookingInvoicing;
use App\Modules\Bookings\Contracts\BookingMetrics;
use App\Modules\Bookings\Contracts\BookingProfitLines;
use App\Modules\Bookings\Contracts\DepartureManifests;
use App\Modules\Bookings\Contracts\TravelerTrips;
use App\Modules\Bookings\Livewire\BookingShow;
use App\Modules\Bookings\Livewire\BookingsIndex;
use App\Modules\Bookings\Livewire\ConvertQuote;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Bookings\Policies\BookingPolicy;
use App\Modules\Bookings\Services\EloquentBookingAccounts;
use App\Modules\Bookings\Services\EloquentBookingInvoicing;
use App\Modules\Bookings\Services\EloquentBookingMetrics;
use App\Modules\Bookings\Services\EloquentBookingProfitLines;
use App\Modules\Bookings\Services\EloquentDepartureManifests;
use App\Modules\Bookings\Services\EloquentTravelerTrips;
use App\Modules\Bookings\Services\NullBookingMetrics;
use App\Modules\Bookings\Services\NullBookingProfitLines;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class BookingsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        BookingAccounts::class => EloquentBookingAccounts::class,
        BookingInvoicing::class => EloquentBookingInvoicing::class,
        TravelerTrips::class => EloquentTravelerTrips::class,
        DepartureManifests::class => EloquentDepartureManifests::class,
    ];

    public function register(): void
    {
        // Contratos que consumen otras capacidades: con Reservas apagada se entrega la implementación nula (ADR-0007).
        Capabilities::bindContract($this->app, Capability::Bookings, BookingProfitLines::class, EloquentBookingProfitLines::class, NullBookingProfitLines::class);
        Capabilities::bindContract($this->app, Capability::Bookings, BookingMetrics::class, EloquentBookingMetrics::class, NullBookingMetrics::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Bookings);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'bookings');

        Livewire::component('bookings.index', BookingsIndex::class);
        Livewire::component('bookings.show', BookingShow::class);
        Livewire::component('bookings.convert-quote', ConvertQuote::class);

        Gate::policy(Booking::class, BookingPolicy::class);
        Capabilities::bindContract($this->app, Capability::Bookings, BookingMetrics::class, EloquentBookingMetrics::class, NullBookingMetrics::class);
    }
}
