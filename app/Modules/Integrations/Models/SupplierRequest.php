<?php

declare(strict_types=1);

namespace App\Modules\Integrations\Models;

use App\Modules\Integrations\Enums\RequestOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $provider
 * @property string $operation
 * @property string $correlation_id
 * @property int|null $http_status
 * @property RequestOutcome $outcome
 * @property int $duration_ms
 * @property string|null $error
 * @property CarbonImmutable $requested_at
 */
final class SupplierRequest extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider', 'operation', 'correlation_id', 'http_status', 'outcome', 'duration_ms', 'error', 'requested_at'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'outcome' => RequestOutcome::class,
            'requested_at' => 'immutable_datetime',
        ];
    }
}
