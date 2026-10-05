<?php

declare(strict_types=1);

namespace App\Modules\Payments\Models;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
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
 * Abono del cliente a un expediente, en la moneda de venta. Un pago aprobado es un movimiento financiero:
 * nunca se borra ni cambia su monto.
 *
 * @property int $id
 * @property string $ulid
 * @property string $booking_ulid
 * @property int $owner_id
 * @property int|null $branch_id
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property int $amount_minor
 * @property string $currency
 * @property string|null $reference
 * @property string|null $gateway
 * @property string|null $gateway_reference
 * @property string|null $link_url
 * @property CarbonImmutable|null $link_expires_at
 * @property string $idempotency_key
 * @property int $recorded_by
 * @property int|null $validated_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $note
 */
final class Payment extends Model
{
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    protected $fillable = ['booking_ulid', 'method', 'amount_minor', 'currency', 'reference', 'note'];

    protected static function booted(): void
    {
        self::deleting(static fn(): never => throw new LogicException('Los pagos no se borran; se anulan con un movimiento inverso.'));
        self::updating(static function (Payment $payment): void {
            if ($payment->getOriginal('status') === PaymentStatus::Approved && $payment->isDirty(['amount_minor', 'currency', 'status'])) {
                throw new LogicException('Un pago aprobado no se modifica.');
            }
        });
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function amount(): Money
    {
        return Money::ofMinor($this->amount_minor, $this->currency);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['method', 'status', 'amount_minor', 'currency', 'reference', 'gateway_reference', 'validated_by', 'approved_at'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Payments->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'link_expires_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
        ];
    }
}
