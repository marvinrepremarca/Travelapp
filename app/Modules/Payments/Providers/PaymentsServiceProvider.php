<?php

declare(strict_types=1);

namespace App\Modules\Payments\Providers;

use App\Modules\Payments\Actions\ExpirePaymentLinksAction;
use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Payments\Contracts\CustomerPayments;
use App\Modules\Payments\Contracts\GatewayWebhooks;
use App\Modules\Payments\Contracts\ReceivedPayments;
use App\Modules\Payments\Contracts\UpcomingBalances;
use App\Modules\Payments\Listeners\ApplyRefundDecision;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Services\EloquentBookingCollections;
use App\Modules\Payments\Services\EloquentReceivedPayments;
use App\Modules\Payments\Services\LedgerCustomerPayments;
use App\Modules\Payments\Services\LedgerUpcomingBalances;
use App\Modules\Payments\Services\WebhookReceiver;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\Routing\PathPrefix;
use App\Modules\Workflow\Events\ApprovalResolved;
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
        ReceivedPayments::class => EloquentReceivedPayments::class,
        BookingCollections::class => EloquentBookingCollections::class,
        UpcomingBalances::class => LedgerUpcomingBalances::class,
        CustomerPayments::class => LedgerCustomerPayments::class,
    ];

    public function boot(): void
    {
        RateLimiter::for(self::WEBHOOK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.payments.webhook_requests_per_minute'))
            ->by((string) $request->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Collections);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'payments');

        Livewire::component('payments.booking', BookingPayments::class);

        // La decisión de finanzas sobre un reembolso llega por evento del módulo Workflow.
        $this->app->make(CapabilitySubscriptions::class)->listen(Capability::Collections, ApprovalResolved::class, ApplyRefundDecision::class, CatchUpPolicy::Replay);

        // Los links vencidos liberan su monto del saldo cobrable.
        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn(): int => app(ExpirePaymentLinksAction::class)->execute(CarbonImmutable::now()))
                ->name('payments:expire-links')
                ->everyFifteenMinutes()
                ->when(static fn(): bool => app(Capabilities::class)->enabled(Capability::Collections))
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
