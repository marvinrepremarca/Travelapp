<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use WeakMap;

/**
 * Bitácora de salida (ADR-0007): el emisor guarda el evento en su misma transacción y se despacha al confirmar.
 * Si nadie lo escucha (capacidad apagada), queda guardado para reprocesarse al encenderla.
 */
final class IntegrationEventOutbox
{
    /** @var WeakMap<IntegrationEvent, int> */
    private WeakMap $storedIds;

    public function __construct(
        private readonly EventSerializer $serializer,
        private readonly Dispatcher $events,
    ) {
        $this->storedIds = new WeakMap();
    }

    public function publish(IntegrationEvent $event): void
    {
        $stored = StoredIntegrationEvent::query()->create([
            'name' => $event::eventName(),
            'payload' => $this->serializer->toPayload($event),
            'occurred_at' => CarbonImmutable::now(),
        ]);
        $this->storedIds[$event] = $stored->id;

        DB::afterCommit(fn() => $this->events->dispatch($event));
    }

    /** Id en la bitácora del evento recién publicado en este proceso; null si no pasó por la bitácora. */
    public function storedIdOf(IntegrationEvent $event): ?int
    {
        return $this->storedIds[$event] ?? null;
    }
}
