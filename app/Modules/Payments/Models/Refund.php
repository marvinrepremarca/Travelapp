<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Devolución de dinero al cliente de un expediente. Requiere aprobación de finanzas; nunca se borra.
 *
 * @property int $id
 * @property string $ulid
 * @property string $booking_ulid
 * @property int $owner_id
 * @property int|null $branch_id
 * @property int $amount_minor
 * @property string $currency
 * @property string $reason
 * @property RefundStatus $status
 * @property string|null $approval_ulid
 * @property int $requested_by
 * @property CarbonImmutable|null $decided_at
 * @property string|null $payout_reference
 * @property int|null $paid_by
 * @property CarbonImmutable|null $paid_at
 */
final class Refund extends Model
{
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    protected $fillable = ['booking_ulid', 'amount_minor', 'currency', 'reason'];

    protected static function booted(): void
    {
        self::deleting(static fn(): never => throw new LogicException('Los reembolsos no se borran.'));
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
            ->logOnly(['amount_minor', 'currency', 'status', 'approval_ulid', 'decided_at', 'payout_reference', 'paid_at'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Payments->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'status' => RefundStatus::class,
            'decided_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }
}
