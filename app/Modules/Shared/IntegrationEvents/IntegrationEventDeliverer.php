<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\Enums\DeliveryOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;

/**
 * Entrega un evento de integración a un suscriptor una sola vez (al menos una vez si falla a medias: los
 * listeners son idempotentes). Si la capacidad está apagada no entrega: queda pendiente o se omite según su política.
 */
final readonly class IntegrationEventDeliverer
{
    private const LOCK_PREFIX = 'integration-delivery:';

    private const LOCK_SEPARATOR = ':';

    public function __construct(
        private Capabilities $capabilities,
        private IntegrationEventOutbox $outbox,
        private Container $container,
    ) {}

    /** Entrega en vivo, justo después de publicar. */
    public function live(Subscription $subscription, IntegrationEvent $event): void
    {
        $storedId = $this->outbox->storedIdOf($event);
        if ($storedId === null) {
            // Evento despachado fuera de la bitácora (p. ej. en pruebas): se comporta como un listener normal.
            $this->invoke($subscription, $event);

            return;
        }

        $this->deliver($subscription, $storedId, $event);
    }

    /** Entrega un evento guardado: en vivo o al reprocesar pendientes. */
    public function deliver(Subscription $subscription, int $storedId, IntegrationEvent $event): void
    {
        if (! $this->capabilities->enabled($subscription->capability)) {
            if ($subscription->catchUp === CatchUpPolicy::Skip) {
                $this->record($subscription, $storedId, DeliveryOutcome::Skipped);
            }

            return;
        }

        if ($this->container->make($subscription->listener) instanceof ShouldQueue) {
            DeliverIntegrationEventJob::for($subscription, $storedId, $this->container->make($subscription->listener));

            return;
        }

        $this->handleNow($subscription, $storedId, $event);
    }

    /** Ejecuta el listener si aún no lo hizo y deja constancia. Si otro proceso lo está entregando, lo deja para él. */
    public function handleNow(Subscription $subscription, int $storedId, IntegrationEvent $event): void
    {
        $lockKey = self::LOCK_PREFIX . $subscription->key() . self::LOCK_SEPARATOR . $storedId;

        Cache::lock($lockKey, config()->integer('capabilities.catch_up.lock_seconds'))->get(function () use ($subscription, $storedId, $event): void {
            if ($this->delivered($subscription, $storedId)) {
                return;
            }

            $this->invoke($subscription, $event);
            $this->record($subscription, $storedId, DeliveryOutcome::Delivered);
        });
    }

    private function invoke(Subscription $subscription, IntegrationEvent $event): void
    {
        $this->container->make($subscription->listener)->{$subscription->method}($event);
    }

    private function delivered(Subscription $subscription, int $storedId): bool
    {
        return IntegrationEventDelivery::query()
            ->where('subscriber', $subscription->key())
            ->where('integration_event_id', $storedId)
            ->exists();
    }

    private function record(Subscription $subscription, int $storedId, DeliveryOutcome $outcome): void
    {
        IntegrationEventDelivery::query()->insertOrIgnore([
            'integration_event_id' => $storedId,
            'subscriber' => $subscription->key(),
            'outcome' => $outcome->value,
            'delivered_at' => CarbonImmutable::now(),
        ]);
    }
}
