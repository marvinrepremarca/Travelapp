<?php

declare(strict_types=1);

namespace App\Modules\Shared\IntegrationEvents;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro inmutable de un evento de integración.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $occurred_at
 */
final class StoredIntegrationEvent extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'integration_events';

    protected $fillable = ['name', 'payload', 'occurred_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
