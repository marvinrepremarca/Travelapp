<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\CashSessionStatus;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Caja diaria de una sucursal: apertura con base, movimientos y cierre con arqueo. No se borra.
 *
 * @property int $id
 * @property string $ulid
 * @property int $branch_id
 * @property int|null $open_branch_key
 * @property string $currency
 * @property int $opening_amount_minor
 * @property int $opened_by
 * @property CarbonImmutable $opened_at
 * @property CashSessionStatus $status
 * @property int|null $expected_amount_minor
 * @property int|null $counted_amount_minor
 * @property int|null $difference_minor
 * @property string|null $closing_note
 * @property int|null $closed_by
 * @property CarbonImmutable|null $closed_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CashMovement> $movements
 * @property-read Branch|null $branch
 */
final class CashSession extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['branch_id', 'currency', 'opening_amount_minor'];

    protected static function booted(): void
    {
        self::deleting(static fn(): never => throw new LogicException('Las cajas no se borran.'));
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return HasMany<CashMovement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class)->orderBy('id');
    }

    /** @return BelongsTo<Branch, $this> */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function money(int $minor): Money
    {
        return Money::ofMinor($minor, $this->currency);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'opening_amount_minor', 'expected_amount_minor', 'counted_amount_minor', 'difference_minor', 'closed_at'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'opening_amount_minor' => 'integer',
            'opened_at' => 'immutable_datetime',
            'status' => CashSessionStatus::class,
            'expected_amount_minor' => 'integer',
            'counted_amount_minor' => 'integer',
            'difference_minor' => 'integer',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
