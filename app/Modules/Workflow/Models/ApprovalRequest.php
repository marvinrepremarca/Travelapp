<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Models;

use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Workflow\Database\Factories\ApprovalRequestFactory;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Solicitud de aprobación de una acción sensible sobre un registro de otro módulo.
 * owner_id = solicitante; la decide un usuario distinto con el permiso del tipo.
 *
 * @property int $id
 * @property string $ulid
 * @property ApprovalType $type
 * @property ApprovalStatus $status
 * @property string $subject_type
 * @property string $subject_id
 * @property string $summary
 * @property string|null $justification
 * @property array<string, mixed>|null $context
 * @property int $owner_id
 * @property int|null $branch_id
 * @property int|null $decided_by
 * @property string|null $decision_note
 * @property CarbonImmutable|null $decided_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable $created_at
 */
final class ApprovalRequest extends Model
{
    /** @use HasFactory<ApprovalRequestFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    /** Estado, solicitante, sucursal y decisión se asignan solo en las Actions. */
    protected $fillable = ['type', 'subject_type', 'subject_id', 'summary', 'justification', 'context', 'expires_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'status', 'decided_by', 'decision_note'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Workflow->value);
    }

    protected static function newFactory(): ApprovalRequestFactory
    {
        return ApprovalRequestFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ApprovalType::class,
            'status' => ApprovalStatus::class,
            'context' => 'array',
            'decided_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
