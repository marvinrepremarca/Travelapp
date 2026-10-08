<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\App;

/**
 * Registro de los listeners que pertenecen a una capacidad (ADR-0007). Reemplaza a `Event::listen` para
 * eventos de integración: entrega solo si la capacidad está encendida y deja constancia para reprocesar.
 */
final class CapabilitySubscriptions
{
    private const DEFAULT_METHOD = 'handle';

    /** @var array<string, Subscription> */
    private array $subscriptions = [];

    public function __construct(private readonly Dispatcher $events) {}

    /**
     * @param  class-string<IntegrationEvent>  $event
     * @param  class-string  $listener
     */
    public function listen(Capability $capability, string $event, string $listener, CatchUpPolicy $catchUp, string $method = self::DEFAULT_METHOD): void
    {
        $subscription = new Subscription($capability, $event, $listener, $method, $catchUp);
        $this->subscriptions[$subscription->key()] = $subscription;

        $this->events->listen($event, static function (IntegrationEvent $published) use ($subscription): void {
            App::make(IntegrationEventDeliverer::class)->live($subscription, $published);
        });
    }

    public function find(string $key): ?Subscription
    {
        return $this->subscriptions[$key] ?? null;
    }

    /** @return list<Subscription> */
    public function all(): array
    {
        return array_values($this->subscriptions);
    }
}
