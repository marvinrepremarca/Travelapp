<?php

declare(strict_types=1);

namespace App\Modules\Operations\Models;

use App\Modules\Operations\Enums\IncidentSeverity;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Incidencia reportada en una salida (retraso, accidente, queja) y su resolución.
 *
 * @property int $id
 * @property string $ulid
 * @property string $departure_ulid
 * @property IncidentSeverity $severity
 * @property string $description
 * @property int $reported_by
 * @property CarbonImmutable $reported_at
 * @property string|null $resolution
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 */
final class DepartureIncident extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['departure_ulid', 'severity', 'description'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function isOpen(): bool
    {
        return $this->resolved_at === null;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['departure_ulid', 'severity', 'resolved_at'])->logOnlyDirty()->useLogName(AuditLogName::Operations->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['severity' => IncidentSeverity::class, 'reported_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime'];
    }
}
