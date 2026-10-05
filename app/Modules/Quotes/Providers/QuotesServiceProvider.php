<?php

declare(strict_types=1);

namespace App\Modules\Quotes\Providers;

use App\Modules\Quotes\Console\ExpireQuotesCommand;
use App\Modules\Quotes\Contracts\AcceptedQuotes;
use App\Modules\Quotes\Contracts\SupplierOfferIntake;
use App\Modules\Quotes\Livewire\PublicQuote;
use App\Modules\Quotes\Livewire\QuoteCreate;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Livewire\QuotesIndex;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Quotes\Policies\QuotePolicy;
use App\Modules\Quotes\Services\EloquentAcceptedQuotes;
use App\Modules\Quotes\Services\EloquentSupplierOfferIntake;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class QuotesServiceProvider extends ServiceProvider
{
    public const CUSTOMER_LINK_LIMITER = 'quote-customer-link';

    /** @var array<class-string, class-string> */
    public array $singletons = [
        AcceptedQuotes::class => EloquentAcceptedQuotes::class,
        SupplierOfferIntake::class => EloquentSupplierOfferIntake::class,
    ];

    public function boot(): void
    {
        RateLimiter::for(self::CUSTOMER_LINK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.quotes.customer_link_requests_per_minute'))
            ->by((string) $request->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'quotes');

        Livewire::component('quotes.index', QuotesIndex::class);
        Livewire::component('quotes.create', QuoteCreate::class);
        Livewire::component('quotes.show', QuoteShow::class);
        Livewire::component('quotes.public', PublicQuote::class);

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
