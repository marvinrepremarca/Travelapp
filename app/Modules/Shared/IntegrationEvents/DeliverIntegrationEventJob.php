<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Entrega en cola para listeners que hablan con terceros (p. ej. facturador electrónico). Hereda los
 * reintentos y la cola del listener original.
 */
final class DeliverIntegrationEventJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public ?int $tries = null;

    /** @var list<int> */
    public array $backoff = [];

    public function __construct(
        public readonly string $subscriber,
        public readonly int $storedId,
    ) {}

    public static function for(Subscription $subscription, int $storedId, object $listener): void
    {
        $job = new self($subscription->key(), $storedId);
        $job->tries = property_exists($listener, 'tries') && is_int($listener->tries) ? $listener->tries : null;
        /** @var list<int> $backoff */
        $backoff = property_exists($listener, 'backoff') && is_array($listener->backoff) ? $listener->backoff : [];
        $job->backoff = $backoff;
        $queue = method_exists($listener, 'viaQueue') ? $listener->viaQueue() : QueueName::Default->value;

        dispatch($job->onQueue(is_string($queue) ? $queue : QueueName::Default->value));
    }

    public function handle(CapabilitySubscriptions $subscriptions, IntegrationEventDeliverer $deliverer, EventSerializer $serializer): void
    {
        $subscription = $subscriptions->find($this->subscriber);
        $stored = StoredIntegrationEvent::query()->find($this->storedId);
        if (! $subscription instanceof Subscription || ! $stored instanceof StoredIntegrationEvent) {
            return;
        }

        $deliverer->handleNow($subscription, $stored->id, $serializer->fromPayload($subscription->event, $stored->payload));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return $this->backoff;
    }
}
