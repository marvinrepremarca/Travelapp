<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Enums\DataRequestType;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Solicitud de un titular de datos (Ley 1581): los datos del titular se cifran en reposo y no se registran en la auditoría.
 *
 * @property int $id
 * @property string $ulid
 * @property string $number
 * @property DataRequestType $type
 * @property DataRequestStatus $status
 * @property string $requester_name
 * @property string $document_number
 * @property string|null $email
 * @property string|null $phone
 * @property string $details
 * @property ConsentChannel $channel
 * @property CarbonImmutable $received_at
 * @property CarbonImmutable $due_on
 * @property string|null $response
 * @property CarbonImmutable|null $resolved_at
 * @property int $handler_id
 * @property int|null $resolved_by
 * @property int $created_by
 * @property-read User $handler
 */
final class DataSubjectRequest extends Model
{
    use HasUlids;
    use LogsActivity;

    protected $fillable = ['type', 'requester_name', 'document_number', 'email', 'phone', 'details', 'channel'];

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
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['number', 'type', 'status', 'due_on', 'handler_id', 'resolved_at'])->logOnlyDirty()->useLogName(AuditLogName::Compliance->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => DataRequestType::class,
            'status' => DataRequestStatus::class,
            'channel' => ConsentChannel::class,
            'requester_name' => 'encrypted',
            'document_number' => 'encrypted',
            'email' => 'encrypted',
            'phone' => 'encrypted',
            'details' => 'encrypted',
            'received_at' => 'immutable_datetime',
            'due_on' => 'immutable_date',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
