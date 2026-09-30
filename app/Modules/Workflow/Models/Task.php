<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Models;

use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Workflow\Database\Factories\TaskFactory;
use App\Modules\Workflow\Enums\TaskPriority;
use App\Modules\Workflow\Enums\TaskStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Tarea asignada a un usuario (owner_id = responsable), opcionalmente ligada a otro registro.
 *
 * @property int $id
 * @property string $ulid
 * @property string $title
 * @property string|null $description
 * @property TaskStatus $status
 * @property TaskPriority $priority
 * @property CarbonImmutable|null $due_at
 * @property CarbonImmutable|null $remind_at
 * @property CarbonImmutable|null $reminded_at
 * @property int $owner_id
 * @property int|null $branch_id
 * @property int $created_by
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property CarbonImmutable|null $completed_at
 */
final class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;
    use SoftDeletes;

    /** Responsable, sucursal, creador y estado se asignan solo en las Actions. */
    protected $fillable = ['title', 'description', 'priority', 'due_at', 'remind_at', 'subject_type', 'subject_id'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    public function isOverdue(CarbonImmutable $now): bool
    {
        return $this->status === TaskStatus::Open && $this->due_at !== null && $this->due_at->lessThan($now);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'priority', 'due_at', 'owner_id'])
            ->logOnlyDirty()
            ->useLogName(AuditLogName::Workflow->value);
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'priority' => TaskPriority::class,
            'due_at' => 'immutable_datetime',
            'remind_at' => 'immutable_datetime',
            'reminded_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
