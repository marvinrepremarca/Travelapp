<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\CatchUpPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Reprocesa, en orden, los eventos que un suscriptor encendido todavía no reconoce (ADR-0007): lo ocurrido
 * mientras su capacidad estuvo apagada o lo que falló en vivo. Idempotente.
 */
final readonly class CatchUpIntegrationEvents
{
    public function __construct(
        private CapabilitySubscriptions $subscriptions,
        private Capabilities $capabilities,
        private IntegrationEventDeliverer $deliverer,
        private EventSerializer $serializer,
    ) {}

    /** @return int eventos entregados o encolados */
    public function run(): int
    {
        $processed = 0;
        foreach ($this->subscriptions->all() as $subscription) {
            if ($subscription->catchUp !== CatchUpPolicy::Replay || ! $this->capabilities->enabled($subscription->capability)) {
                continue;
            }

            $this->pending($subscription)->chunkById(
                config()->integer('capabilities.catch_up.chunk'),
                function (Collection $events) use ($subscription, &$processed): void {
                    foreach ($events as $stored) {
                        /** @var StoredIntegrationEvent $stored */
                        $this->deliverer->deliver($subscription, $stored->id, $this->serializer->fromPayload($subscription->event, $stored->payload));
                        $processed++;
                    }
                },
            );
        }

        return $processed;
    }

    /** @return Builder<StoredIntegrationEvent> */
    private function pending(Subscription $subscription): Builder
    {
        return StoredIntegrationEvent::query()
            ->where('name', $subscription->event::eventName())
            ->whereNotExists(static fn(QueryBuilder $query) => $query->selectRaw('1')
                ->from('integration_event_deliveries')
                ->whereColumn('integration_event_deliveries.integration_event_id', 'integration_events.id')
                ->where('integration_event_deliveries.subscriber', $subscription->key()));
    }
}
