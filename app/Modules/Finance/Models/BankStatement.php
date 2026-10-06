<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Extracto bancario cargado (CSV).
 *
 * @property int $id
 * @property string $ulid
 * @property int $agency_bank_account_id
 * @property string $file_name
 * @property string $file_hash
 * @property int $lines_imported
 * @property int $lines_skipped
 * @property CarbonImmutable|null $first_posted_on
 * @property CarbonImmutable|null $last_posted_on
 * @property int $imported_by
 * @property CarbonImmutable $imported_at
 */
final class BankStatement extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['agency_bank_account_id', 'file_name', 'file_hash', 'lines_imported', 'lines_skipped', 'first_posted_on', 'last_posted_on', 'imported_by', 'imported_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<AgencyBankAccount, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(AgencyBankAccount::class, 'agency_bank_account_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['file_name', 'lines_imported', 'lines_skipped'])->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'lines_imported' => 'integer',
            'lines_skipped' => 'integer',
            'first_posted_on' => 'immutable_date',
            'last_posted_on' => 'immutable_date',
            'imported_at' => 'immutable_datetime',
        ];
    }
}
