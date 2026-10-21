<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Retención de la bitácora de integración (ADR-0007). Borra:
 * - lo que ya recibieron todos sus suscriptores, pasados `delivered_days` (margen para revisar incidentes);
 * - lo que sigue pendiente, pasados `pending_days`: una capacidad que no se encendió en ese plazo ya no lo reprocesa.
 * Los datos de negocio viven en sus propias tablas: aquí solo se limpian los mensajes entre capacidades.
 */
final readonly class PruneIntegrationEvents
{
    public function __construct(private CapabilitySubscriptions $subscriptions) {}

    /** @return int eventos borrados */
    public function run(CarbonImmutable $now): int
    {
        $deliveredBefore = $now->subDays(config()->integer('capabilities.retention.delivered_days'));
        $pendingBefore = $now->subDays(config()->integer('capabilities.retention.pending_days'));

        $deleted = $this->deleteChunked(StoredIntegrationEvent::query()->where('occurred_at', '<', $pendingBefore));

        foreach ($this->subscribersByEvent() as $name => $subscribers) {
            $deleted += $this->deleteChunked($this->fullyDelivered($name, $subscribers)->where('occurred_at', '<', $deliveredBefore));
        }

        // Eventos que nadie escucha en este despliegue: no hay a quién entregarlos.
        return $deleted + $this->deleteChunked(StoredIntegrationEvent::query()
            ->whereNotIn('name', array_keys($this->subscribersByEvent()))
            ->where('occurred_at', '<', $deliveredBefore));
    }

    /**
     * @param  list<string>  $subscribers
     * @return Builder<StoredIntegrationEvent>
     */
    private function fullyDelivered(string $name, array $subscribers): Builder
    {
        $query = StoredIntegrationEvent::query()->where('name', $name);
        foreach ($subscribers as $subscriber) {
            $query->whereExists(static fn(QueryBuilder $delivered) => $delivered->selectRaw('1')
                ->from('integration_event_deliveries')
                ->whereColumn('integration_event_deliveries.integration_event_id', 'integration_events.id')
                ->where('integration_event_deliveries.subscriber', $subscriber));
        }

        return $query;
    }

    /** @param  Builder<StoredIntegrationEvent>  $query */
    private function deleteChunked(Builder $query): int
    {
        $deleted = 0;
        do {
            $ids = (clone $query)->orderBy('id')->limit(config()->integer('capabilities.retention.chunk'))->pluck('id')->all();
            if ($ids === []) {
                break;
            }

            DB::transaction(static function () use ($ids): void {
                IntegrationEventDelivery::query()->whereIn('integration_event_id', $ids)->delete();
                StoredIntegrationEvent::query()->whereIn('id', $ids)->delete();
            });
            $deleted += count($ids);
        } while (true);

        return $deleted;
    }

    /** @return array<string, list<string>> nombre del evento → claves de sus suscriptores */
    private function subscribersByEvent(): array
    {
        $byEvent = [];
        foreach ($this->subscriptions->all() as $subscription) {
            $byEvent[$subscription->event::eventName()][] = $subscription->key();
        }

        return $byEvent;
    }
}
