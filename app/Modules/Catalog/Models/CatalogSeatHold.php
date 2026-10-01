<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Models;

use App\Modules\Catalog\Enums\SeatHoldStatus;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cupos apartados en una salida para una reserva (referencia del expediente) hasta que se liberen.
 *
 * @property int $id
 * @property string $ulid
 * @property int $departure_id
 * @property int $seats
 * @property SeatHoldStatus $status
 * @property string $reference
 * @property string $idempotency_key
 * @property CarbonImmutable|null $released_at
 */
final class CatalogSeatHold extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['departure_id', 'seats', 'reference', 'idempotency_key'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<CatalogDeparture, $this> */
    public function departure(): BelongsTo
    {
        return $this->belongsTo(CatalogDeparture::class, 'departure_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['seats', 'status', 'reference', 'released_at'])->logOnlyDirty()->useLogName(AuditLogName::Catalog->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['seats' => 'integer', 'status' => SeatHoldStatus::class, 'released_at' => 'immutable_datetime'];
    }
}
