<?php

declare(strict_types=1);

namespace App\Modules\Operations\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Vehículo para traslados y tours.
 *
 * @property int $id
 * @property string $ulid
 * @property string $plate
 * @property string $description
 * @property int $capacity
 * @property bool $is_active
 */
final class Vehicle extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['plate', 'description', 'capacity', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Operations->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['capacity' => 'integer', 'is_active' => 'boolean'];
    }
}
