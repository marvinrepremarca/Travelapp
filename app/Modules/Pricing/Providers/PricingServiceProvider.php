<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Providers;

use App\Modules\Pricing\Console\FetchOfficialRateCommand;
use App\Modules\Pricing\Contracts\ExchangeRates;
use App\Modules\Pricing\Livewire\ExchangeRatesManager;
use App\Modules\Pricing\Services\DatabaseExchangeRates;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class PricingServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        ExchangeRates::class => DatabaseExchangeRates::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'pricing');

        Livewire::component('pricing.exchange-rates-manager', ExchangeRatesManager::class);

        if ($this->app->runningInConsole()) {
            $this->commands([FetchOfficialRateCommand::class]);
        }

        // La TRM del día se publica la víspera; se reintenta varias veces en la mañana por si la fuente falla.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            foreach (config()->array('travel.pricing.official_rate_fetch_times') as $time) {
                $schedule->command(FetchOfficialRateCommand::class)
                    ->dailyAt((string) $time)
                    ->timezone(config()->string('travel.agency.timezone'))
                    ->withoutOverlapping()
                    ->onOneServer();
            }
        });
    }
}
