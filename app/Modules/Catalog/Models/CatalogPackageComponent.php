<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Producto propio incluido en un paquete, en un día del itinerario.
 *
 * @property int $id
 * @property string $ulid
 * @property int $package_id
 * @property int $component_id
 * @property int $day_offset
 * @property-read CatalogProduct $component
 */
final class CatalogPackageComponent extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['package_id', 'component_id', 'day_offset'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<CatalogProduct, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'component_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['day_offset' => 'integer'];
    }
}
