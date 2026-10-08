<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use App\Modules\Shared\Enums\DeliveryOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Constancia de que un suscriptor ya procesó (u omitió) un evento: evita duplicados al reprocesar.
 *
 * @property int $id
 * @property int $integration_event_id
 * @property string $subscriber
 * @property DeliveryOutcome $outcome
 * @property CarbonImmutable $delivered_at
 */
final class IntegrationEventDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = ['integration_event_id', 'subscriber', 'outcome', 'delivered_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['outcome' => DeliveryOutcome::class, 'delivered_at' => 'immutable_datetime'];
    }
}
