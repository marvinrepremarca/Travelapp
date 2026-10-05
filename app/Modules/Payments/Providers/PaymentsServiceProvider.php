<?php

declare(strict_types=1);

namespace App\Modules\Payments\Providers;

use App\Modules\Payments\Actions\ExpirePaymentLinksAction;
use App\Modules\Payments\Contracts\GatewayWebhooks;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Services\WebhookReceiver;
use App\Modules\Shared\Routing\PathPrefix;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class PaymentsServiceProvider extends ServiceProvider
{
    public const WEBHOOK_LIMITER = 'payment-webhooks';

    /** @var array<class-string, class-string> */
    public array $singletons = [
        GatewayWebhooks::class => WebhookReceiver::class,
    ];

    public function boot(): void
    {
        RateLimiter::for(self::WEBHOOK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.payments.webhook_requests_per_minute'))
            ->by((string) $request->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'payments');

        Livewire::component('payments.booking', BookingPayments::class);

        // Los links vencidos liberan su monto del saldo cobrable.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn(): int => app(ExpirePaymentLinksAction::class)->execute(CarbonImmutable::now()))
                ->name('payments:expire-links')
                ->everyFifteenMinutes()
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
