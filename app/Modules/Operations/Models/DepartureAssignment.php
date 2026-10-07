<?php

declare(strict_types=1);

namespace App\Modules\Operations\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Guía y vehículo asignados a una salida, con su ventana horaria en UTC.
 *
 * @property int $id
 * @property string $departure_ulid
 * @property int|null $guide_id
 * @property int|null $vehicle_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property int $assigned_by
 * @property-read Guide|null $guide
 * @property-read Vehicle|null $vehicle
 */
final class DepartureAssignment extends Model
{
    use LogsActivity;

    protected $fillable = ['departure_ulid', 'guide_id', 'vehicle_id', 'starts_at', 'ends_at', 'assigned_by'];

    /** @return BelongsTo<Guide, $this> */
    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['departure_ulid', 'guide_id', 'vehicle_id'])->logOnlyDirty()->useLogName(AuditLogName::Operations->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }
}
