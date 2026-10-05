<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Obligación con un proveedor por un servicio confirmado (neto del proveedor). Movimiento financiero: no se borra.
 *
 * @property int $id
 * @property string $ulid
 * @property string $booking_item_ulid
 * @property string $booking_ulid
 * @property string $booking_number
 * @property int $supplier_id
 * @property int $owner_id
 * @property int|null $branch_id
 * @property string $description
 * @property int $amount_minor
 * @property string $currency
 * @property CarbonImmutable $service_date
 * @property CarbonImmutable $due_date
 * @property PayableStatus $status
 * @property string|null $payment_reference
 * @property int|null $paid_by
 * @property CarbonImmutable|null $paid_at
 * @property-read Supplier $supplier
 */
final class SupplierPayable extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = [
        'booking_item_ulid', 'booking_ulid', 'booking_number', 'supplier_id', 'owner_id', 'branch_id', 'description',
        'amount_minor', 'currency', 'service_date', 'due_date',
    ];

    protected static function booted(): void
    {
        self::deleting(static fn(): never => throw new LogicException('Las cuentas por pagar no se borran; se anulan.'));
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<Supplier, $this> */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function amount(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    public function isOverdue(CarbonImmutable $today): bool
    {
        return $this->status === PayableStatus::Open && $this->due_date->lessThan($today);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount_minor', 'due_date', 'payment_reference', 'paid_at'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'service_date' => 'immutable_date',
            'due_date' => 'immutable_date',
            'status' => PayableStatus::class,
            'paid_at' => 'immutable_datetime',
        ];
    }
}
