<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Providers;

use App\Modules\Integrations\Adapters\DatosGov\DatosGovTrmSource;
use App\Modules\Integrations\Adapters\Fake\FakeFlights;
use App\Modules\Integrations\Adapters\Fake\FakeHotels;
use App\Modules\Pricing\Contracts\OfficialExchangeRateSource;
use App\Modules\Search\Contracts\FlightProvider;
use App\Modules\Search\Contracts\HotelProvider;
use Illuminate\Support\ServiceProvider;

/** Enlaza los puertos de los demás módulos con los adaptadores de proveedores externos. */
final class IntegrationsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        OfficialExchangeRateSource::class => DatosGovTrmSource::class,
    ];

    public function register(): void
    {
        // Adaptadores por producto (ADR-0006): el agregador de Search usa los activos según configuración.
        $this->app->tag([FakeFlights::class], FlightProvider::TAG);
        $this->app->tag([FakeHotels::class], HotelProvider::TAG);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
