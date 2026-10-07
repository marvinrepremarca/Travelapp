<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Enums\ComplianceDocumentType;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Documento legal con vigencia (RNT, póliza…). Se alerta antes de su vencimiento.
 *
 * @property int $id
 * @property string $ulid
 * @property ComplianceDocumentType $type
 * @property string $number
 * @property string|null $issuer
 * @property CarbonImmutable|null $starts_on
 * @property CarbonImmutable $expires_on
 * @property int $responsible_id
 * @property string|null $notes
 * @property int $created_by
 * @property-read User $responsible
 */
final class ComplianceDocument extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['type', 'number', 'issuer', 'starts_on', 'expires_on', 'responsible_id', 'notes'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return BelongsTo<User, $this> */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['type', 'number', 'issuer', 'starts_on', 'expires_on', 'responsible_id'])->logOnlyDirty()->useLogName(AuditLogName::Compliance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => ComplianceDocumentType::class, 'starts_on' => 'immutable_date', 'expires_on' => 'immutable_date'];
    }
}
