<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Shared\Routing\PathPrefix;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

    public function boot(): void
    {
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

        RateLimiter::for('health', static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.health.requests_per_minute'))
            ->by((string) $request->ip()));
    }
}
