<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\CashMovementType;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Entrada o salida de efectivo. Inmutable: un error se corrige con un movimiento inverso.
 *
 * @property int $id
 * @property string $ulid
 * @property int $cash_session_id
 * @property CashMovementType $type
 * @property int $amount_minor
 * @property string $description
 * @property string|null $payment_ulid
 * @property int $recorded_by
 * @property CarbonImmutable $recorded_at
 */
final class CashMovement extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['cash_session_id', 'type', 'amount_minor', 'description', 'payment_ulid', 'recorded_by', 'recorded_at'];

    protected static function booted(): void
    {
        self::updating(static fn(): never => throw new LogicException('Los movimientos de caja no se editan.'));
        self::deleting(static fn(): never => throw new LogicException('Los movimientos de caja no se borran.'));
    }

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => CashMovementType::class, 'amount_minor' => 'integer', 'recorded_at' => 'immutable_datetime'];
    }
}
