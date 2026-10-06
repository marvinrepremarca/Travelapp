<?php

declare(strict_types=1);

namespace App\Modules\Finance\Models;

use App\Modules\Finance\Data\StatementFormat;
use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Cuenta bancaria de la agencia que se concilia con sus extractos.
 *
 * @property int $id
 * @property string $ulid
 * @property string $name
 * @property string $bank_name
 * @property string $account_last_digits
 * @property string $currency
 * @property array<string, mixed> $statement_format
 * @property bool $is_active
 */
final class AgencyBankAccount extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['name', 'bank_name', 'account_last_digits', 'currency', 'statement_format', 'is_active'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function format(): StatementFormat
    {
        return StatementFormat::fromArray($this->statement_format);
    }

    public function label(): string
    {
        return "{$this->name} · {$this->bank_name} ···{$this->account_last_digits} ({$this->currency})";
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->useLogName(AuditLogName::Finance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['statement_format' => 'array', 'is_active' => 'boolean'];
    }
}
