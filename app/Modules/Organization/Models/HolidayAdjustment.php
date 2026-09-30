<?php

declare(strict_types=1);

namespace App\Modules\Organization\Models;

use App\Modules\Organization\Database\Factories\HolidayAdjustmentFactory;
use App\Modules\Organization\Enums\HolidayAdjustmentType;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ajuste de la agencia sobre el calendario nacional.
 *
 * @property int $id
 * @property string $ulid
 * @property CarbonImmutable $date
 * @property HolidayAdjustmentType $type
 * @property string $name
 */
final class HolidayAdjustment extends Model
{
    /** @use HasFactory<HolidayAdjustmentFactory> */
    use HasFactory;
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['date', 'type', 'name'];

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
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Organization->value);
    }

    protected static function newFactory(): HolidayAdjustmentFactory
    {
        return HolidayAdjustmentFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'type' => HolidayAdjustmentType::class,
        ];
    }
}
