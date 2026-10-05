<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Evento recibido de una pasarela (webhook). Se guarda solo lo necesario: nunca el payload completo con datos del pagador.
 *
 * @property int $id
 * @property string $gateway
 * @property string $event_id
 * @property string $gateway_reference
 * @property PaymentStatus $outcome
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable|null $processed_at
 */
final class PaymentGatewayEvent extends Model
{
    protected $fillable = ['gateway', 'event_id', 'gateway_reference', 'outcome', 'received_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['outcome' => PaymentStatus::class, 'received_at' => 'immutable_datetime', 'processed_at' => 'immutable_datetime'];
    }
}
