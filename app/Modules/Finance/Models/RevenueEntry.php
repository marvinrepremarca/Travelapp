<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Database\Factories\RevenueEntryFactory;
use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Ingreso contable reconocido (o su reverso). Movimiento financiero: no se edita ni se borra.
 *
 * @property int $id
 * @property string $ulid
 * @property RevenueSource $source
 * @property RevenueEntryType $entry_type
 * @property string|null $booking_item_ulid
 * @property string|null $booking_number
 * @property string|null $customer_name
 * @property string $description
 * @property int $amount_minor
 * @property string $currency
 * @property CarbonImmutable $recognized_on
 * @property int $owner_id
 * @property int|null $branch_id
 * @property int|null $created_by
 */
final class RevenueEntry extends Model
{
    /** @use HasFactory<RevenueEntryFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    protected $fillable = [
        'booking_item_ulid', 'booking_number', 'customer_name', 'description', 'amount_minor', 'currency',
        'recognized_on', 'owner_id', 'branch_id', 'created_by',
    ];

    protected static function booted(): void
    {
        self::updating(static fn(): never => throw new LogicException('Los ingresos no se editan; se reversan.'));
        self::deleting(static fn(): never => throw new LogicException('Los ingresos no se borran; se reversan.'));
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function amount(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['source', 'entry_type', 'amount_minor', 'currency', 'recognized_on', 'booking_item_ulid'])
            ->useLogName(AuditLogName::Finance->value);
    }

    protected static function newFactory(): RevenueEntryFactory
    {
        return RevenueEntryFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'source' => RevenueSource::class,
            'entry_type' => RevenueEntryType::class,
            'amount_minor' => 'integer',
            'recognized_on' => 'immutable_date',
        ];
    }
}
