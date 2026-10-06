<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Enums\ReconciliationTarget;
use App\Modules\Finance\Enums\StatementLineStatus;
use App\Modules\Shared\Enums\AuditLogName;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Movimiento del extracto bancario. El importe y la fecha no cambian; solo su estado de conciliación.
 *
 * @property int $id
 * @property string $ulid
 * @property int $bank_statement_id
 * @property int $agency_bank_account_id
 * @property CarbonImmutable $posted_on
 * @property string $description
 * @property string|null $reference
 * @property int $amount_minor
 * @property string $currency
 * @property string $line_hash
 * @property StatementLineStatus $status
 * @property ReconciliationTarget|null $matched_target
 * @property string|null $matched_ulid
 * @property string|null $note
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 */
final class BankStatementLine extends Model
{
    use HasUlids;
    use LogsActivity;

    private const IMMUTABLE = ['posted_on', 'description', 'reference', 'amount_minor', 'currency', 'line_hash', 'agency_bank_account_id', 'bank_statement_id'];

    protected $fillable = ['bank_statement_id', 'agency_bank_account_id', 'posted_on', 'description', 'reference', 'amount_minor', 'currency', 'line_hash', 'status'];

    protected static function booted(): void
    {
        self::updating(static function (self $line): void {
            if ($line->isDirty(self::IMMUTABLE)) {
                throw new LogicException('Los datos del extracto bancario no se editan.');
            }
        });
        self::deleting(static fn(): never => throw new LogicException('Las líneas del extracto no se borran.'));
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

    public function isInflow(): bool
    {
        return $this->amount_minor > 0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'matched_target', 'matched_ulid', 'note'])->logOnlyDirty()->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'posted_on' => 'immutable_date',
            'amount_minor' => 'integer',
            'status' => StatementLineStatus::class,
            'matched_target' => ReconciliationTarget::class,
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
