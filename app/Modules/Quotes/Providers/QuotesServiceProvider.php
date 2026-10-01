<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Providers;

use App\Modules\Quotes\Console\ExpireQuotesCommand;
use App\Modules\Quotes\Livewire\QuoteCreate;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Livewire\QuotesIndex;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Policies\QuotePolicy;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class QuotesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'quotes');

        Livewire::component('quotes.index', QuotesIndex::class);
        Livewire::component('quotes.create', QuoteCreate::class);
        Livewire::component('quotes.show', QuoteShow::class);

        Gate::policy(Quote::class, QuotePolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([ExpireQuotesCommand::class]);
        }

        // Aceptar ya valida la vigencia; esto mantiene los listados y reportes al día.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->command(ExpireQuotesCommand::class)->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
        });
    }
}
