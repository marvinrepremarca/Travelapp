<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Enums\ObligationRecurrence;
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
 * Obligación del calendario legal (declaraciones, renovaciones, reportes) con responsable y periodicidad.
 *
 * @property int $id
 * @property string $ulid
 * @property string $title
 * @property string|null $description
 * @property CarbonImmutable $due_on
 * @property ObligationRecurrence $recurrence
 * @property int $responsible_id
 * @property CarbonImmutable|null $completed_at
 * @property int|null $completed_by
 * @property int $created_by
 * @property-read User $responsible
 */
final class ComplianceObligation extends Model
{
    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'due_on', 'recurrence', 'responsible_id'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function isPending(): bool
    {
        return $this->completed_at === null;
    }

    /** @return BelongsTo<User, $this> */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'due_on', 'recurrence', 'responsible_id', 'completed_at'])->logOnlyDirty()->useLogName(AuditLogName::Compliance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['due_on' => 'immutable_date', 'recurrence' => ObligationRecurrence::class, 'completed_at' => 'immutable_datetime'];
    }
}
