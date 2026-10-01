<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Salida de un producto: fecha y hora locales del destino (zona del producto) con su cupo.
 *
 * @property int $id
 * @property string $ulid
 * @property int $product_id
 * @property CarbonImmutable $service_date
 * @property string $starts_at
 * @property int $capacity
 * @property int $reserved_seats
 * @property DepartureStatus $status
 * @property-read CatalogProduct $product
 */
final class CatalogDeparture extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['product_id', 'service_date', 'starts_at', 'capacity'];

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

    public function availableSeats(): int
    {
        return max($this->capacity - $this->reserved_seats, 0);
    }

    public function isSellable(): bool
    {
        return $this->status === DepartureStatus::Open && $this->availableSeats() > 0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['service_date', 'starts_at', 'capacity', 'reserved_seats', 'status'])->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'service_date' => 'immutable_date',
            'capacity' => 'integer',
            'reserved_seats' => 'integer',
            'status' => DepartureStatus::class,
        ];
    }
}
