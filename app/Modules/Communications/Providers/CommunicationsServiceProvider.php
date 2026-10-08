<?php

declare(strict_types=1);

namespace App\Modules\Communications\Providers;

use App\Modules\Communications\Actions\SendBalanceRemindersAction;
use App\Modules\Communications\Contracts\ConversationTranscripts;
use App\Modules\Communications\Contracts\CustomerNotices;
use App\Modules\Communications\Contracts\InboundMessages;
use App\Modules\Communications\Listeners\SendPaymentNotices;
use App\Modules\Communications\Listeners\SendQuoteNotice;
use App\Modules\Communications\Livewire\ConversationsInbox;
use App\Modules\Communications\Services\EloquentConversationTranscripts;
use App\Modules\Communications\Services\NotifierCustomerNotices;
use App\Modules\Communications\Services\NullCustomerNotices;
use App\Modules\Communications\Services\WebhookIntake;
use App\Modules\Payments\Events\PaymentLinkCreated;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\Routing\PathPrefix;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class CommunicationsServiceProvider extends ServiceProvider
{
    public const WEBHOOK_LIMITER = 'messaging-webhooks';

    /** @var array<class-string, class-string> */
    public array $singletons = [
        InboundMessages::class => WebhookIntake::class,
        ConversationTranscripts::class => EloquentConversationTranscripts::class,
    ];

    public function register(): void
    {
        // Contratos que consumen otras capacidades: con Mensajería apagada se entrega la implementación nula (ADR-0007).
        Capabilities::bindContract($this->app, Capability::Messaging, CustomerNotices::class, NotifierCustomerNotices::class, NullCustomerNotices::class);
    }

    public function boot(): void
    {
        RateLimiter::for(self::WEBHOOK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.communications.webhook_requests_per_minute'))
            ->by((string) $request->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php', Capability::Messaging);
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'communications');

        Livewire::component('communications.inbox', ConversationsInbox::class);

        // Avisos automáticos por WhatsApp. Con Mensajería apagada se omiten: un aviso tardío confunde al cliente.
        $subscriptions = $this->app->make(CapabilitySubscriptions::class);
        $subscriptions->listen(Capability::Messaging, QuoteSent::class, SendQuoteNotice::class, CatchUpPolicy::Skip);
        $subscriptions->listen(Capability::Messaging, PaymentLinkCreated::class, SendPaymentNotices::class, CatchUpPolicy::Skip, 'handleLink');
        $subscriptions->listen(Capability::Messaging, PaymentReceived::class, SendPaymentNotices::class, CatchUpPolicy::Skip, 'handleReceived');

        $this->callAfterResolving(Schedule::class, static function (Schedule $schedule): void {
            $schedule->call(static fn(): int => app(SendBalanceRemindersAction::class)->execute(CarbonImmutable::now(config()->string('travel.agency.timezone'))))
                ->name('communications:balance-reminders')
                ->dailyAt(config()->string('travel.communications.balance_reminder_time'))
                ->timezone(config()->string('travel.agency.timezone'))
                ->when(static fn(): bool => app(Capabilities::class)->enabled(Capability::Messaging))
                ->withoutOverlapping()
                ->onOneServer();
        });
    }
}
