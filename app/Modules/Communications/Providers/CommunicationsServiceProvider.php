<?php

declare(strict_types=1);

namespace App\Modules\Communications\Providers;

use App\Modules\Communications\Contracts\ConversationTranscripts;
use App\Modules\Communications\Contracts\InboundMessages;
use App\Modules\Communications\Livewire\ConversationsInbox;
use App\Modules\Communications\Services\EloquentConversationTranscripts;
use App\Modules\Communications\Services\WebhookIntake;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Cache\RateLimiting\Limit;
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

    public function boot(): void
    {
        RateLimiter::for(self::WEBHOOK_LIMITER, static fn(Request $request): Limit => Limit::perMinute(config()->integer('travel.communications.webhook_requests_per_minute'))
            ->by((string) $request->ip()));

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        if (! $this->app->routesAreCached()) {
            PathPrefix::load(__DIR__ . '/../Routes/web.php');
        }
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'communications');

        Livewire::component('communications.inbox', ConversationsInbox::class);
    }
}
