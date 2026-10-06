<?php

declare(strict_types=1);

namespace App\Modules\Communications\Models;

use App\Modules\Communications\Enums\BotStep;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Shared\Concerns\HasVisibilityScope;
use App\Modules\Shared\Contracts\ScopedViewer;
use App\Modules\Shared\Enums\AuditLogName;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Conversación de WhatsApp con un contacto.
 *
 * @property int $id
 * @property string $ulid
 * @property string $channel
 * @property string $contact_phone
 * @property string $contact_phone_hash
 * @property string|null $contact_name
 * @property ConversationStatus $status
 * @property BotStep|null $bot_step
 * @property array<string, string|int|null> $bot_data
 * @property string|null $lead_ulid
 * @property int|null $owner_id
 * @property int|null $branch_id
 * @property CarbonImmutable $last_message_at
 * @property-read Collection<int, ConversationMessage> $messages
 */
final class Conversation extends Model
{
    use HasUlids;
    use HasVisibilityScope;
    use LogsActivity;

    protected $fillable = [];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function getRouteKeyName(): string
    {
        return 'ulid';
    }

    /** @return HasMany<ConversationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->orderBy('id');
    }

    /**
     * Las que nadie ha tomado las ve todo el equipo; las asignadas, según el alcance de quien consulta.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inboxFor(Builder $query, ScopedViewer $viewer): void
    {
        $query->where(fn(Builder $inner) => $inner->whereNull('owner_id')->orWhere(fn(Builder $assigned) => $this->visibleTo($assigned, $viewer)));
    }

    public function isAvailableTo(ScopedViewer $viewer): bool
    {
        return $this->owner_id === null || $this->isVisibleTo($viewer);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'owner_id', 'lead_ulid'])->logOnlyDirty()->useLogName(AuditLogName::Communications->value);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'contact_phone' => 'encrypted',
            'status' => ConversationStatus::class,
            'bot_step' => BotStep::class,
            'bot_data' => 'array',
            'last_message_at' => 'immutable_datetime',
        ];
    }
}
