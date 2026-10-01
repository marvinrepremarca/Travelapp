<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Temporada de un producto con fechas inclusivas; las temporadas de un producto no se cruzan.
 *
 * @property int $id
 * @property string $ulid
 * @property int $product_id
 * @property string $name
 * @property CarbonImmutable $starts_on
 * @property CarbonImmutable $ends_on
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CatalogRate> $rates
 */
final class CatalogSeason extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['product_id', 'name', 'starts_on', 'ends_on'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<CatalogProduct, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogProduct::class, 'product_id');
    }

    /** @return HasMany<CatalogRate, $this> */
    public function rates(): HasMany
    {
        return $this->hasMany(CatalogRate::class, 'season_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }
}
