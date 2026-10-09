<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\IntegrationEvents\CatchUpIntegrationEvents;
use App\Modules\Shared\IntegrationEvents\IntegrationEventOutbox;
use App\Modules\Shared\Routing\PathPrefix;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;

final class AppServiceProvider extends ServiceProvider
{
    private const PASSWORD_MIN_LENGTH = 12;

    private const SEARCH_REQUESTS_PER_MINUTE = 30;

    /** @var array<class-string, class-string> */
    public array $singletons = [
        Capabilities::class => Capabilities::class,
        IntegrationEventOutbox::class => IntegrationEventOutbox::class,
        CapabilitySubscriptions::class => CapabilitySubscriptions::class,
    ];

    public function boot(): void
    {
        // Falla cerrado si el despliegue enciende una capacidad sin la que necesita; la consola queda libre
        // para diagnosticar con `capabilities:status`.
        if (! $this->app->runningInConsole()) {
            $this->app->make(Capabilities::class)->assertConsistent();
        }

        $production = $this->app->isProduction();

        Model::shouldBeStrict(! $production);
        Model::unguard(false);
        Date::use(CarbonImmutable::class);
        DB::prohibitDestructiveCommands($production);
        URL::forceHttps($production);
        Vite::useAggressivePrefetching();

        // Livewire publica su script y su endpoint bajo la misma carpeta que la aplicación.
        Livewire::setUpdateRoute(static fn($handle) => Route::post(PathPrefix::path('livewire/update'), $handle)->middleware('web'));
        Livewire::setScriptRoute(static fn($handle) => Route::get(PathPrefix::path('livewire/livewire.js'), $handle));

        Password::defaults(static fn(): Password => Password::min(self::PASSWORD_MIN_LENGTH)
            ->mixedCase()
            ->numbers()
            ->when($production, static fn(Password $rule): Password => $rule->uncompromised()));

        RateLimiter::for('search', static fn(Request $request): Limit => Limit::perMinute(self::SEARCH_REQUESTS_PER_MINUTE)
            ->by((string) ($request->user()?->getAuthIdentifier() ?? $request->ip())));

        // Las capacidades encendidas reconocen lo que ocurrió mientras estuvieron apagadas (ADR-0007).
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn(): int => app(CatchUpIntegrationEvents::class)->run())
                ->name('capabilities:catch-up')
                ->cron(config()->string('capabilities.catch_up.cron'))
                ->withoutOverlapping()
                ->onOneServer();
        });

        // @capability(Capability::X) … @endcapability: secciones que solo existen con su capacidad encendida.
        Blade::if('capability', static fn(Capability $capability): bool => app(Capabilities::class)->enabled($capability));

        RateLimiter::for('health', static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.health.requests_per_minute'))
            ->by((string) $request->ip()));
    }
}
