<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\PassengerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Costo neto por pasajero de una temporada, según el tipo de pasajero (edad a la fecha del servicio).
 *
 * @property int $id
 * @property int $season_id
 * @property PassengerType $passenger_type
 * @property int $net_amount_minor
 */
final class CatalogRate extends Model
{
    use LogsActivity;

    protected $fillable = ['season_id', 'passenger_type', 'net_amount_minor'];

    /** @return BelongsTo<CatalogSeason, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(CatalogSeason::class, 'season_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['passenger_type' => PassengerType::class, 'net_amount_minor' => 'integer'];
    }
}
