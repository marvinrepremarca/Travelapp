<?php

declare(strict_types=1);

namespace App\Modules\Portal\Providers;

use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Portal\Listeners\SendTripPortalLink;
use App\Modules\Portal\Livewire\RequestAccess;
use App\Modules\Portal\Livewire\ShopIndex;
use App\Modules\Portal\Livewire\ShopProductPage;
use App\Modules\Portal\Livewire\TripPortal;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/** Portal del viajero "Mi viaje" (B2C, sin contraseña). */
final class PortalServiceProvider extends ServiceProvider
{
    public const LINK_LIMITER = 'portal-links';

    public function boot(): void
    {
        RateLimiter::for(self::LINK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.portal.requests_per_minute'))
            ->by((string) $request->ip()));

        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Portals);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'portal');

        Livewire::component('portal.trip', TripPortal::class);
        Livewire::component('portal.access', RequestAccess::class);
        Livewire::component('portal.shop', ShopIndex::class);
        Livewire::component('portal.shop-product', ShopProductPage::class);

        Event::listen(BookingItemConfirmed::class, SendTripPortalLink::class);
    }
}
