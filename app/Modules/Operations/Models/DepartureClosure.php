<?php

declare(strict_types=1);

namespace App\Modules\Operations\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cierre operativo de una salida: asistentes, no presentados y observaciones. Una salida cerrada ya no cambia de guía ni vehículo.
 *
 * @property int $id
 * @property string $departure_ulid
 * @property int $attended
 * @property int $no_shows
 * @property string|null $notes
 * @property int $closed_by
 * @property CarbonImmutable $closed_at
 */
final class DepartureClosure extends Model
{
    use LogsActivity;

    protected $fillable = ['departure_ulid', 'attended', 'no_shows', 'notes'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['departure_ulid', 'attended', 'no_shows'])->useLogName(AuditLogName::Operations->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['closed_at' => 'immutable_datetime'];
    }
}
