<?php

declare(strict_types=1);

namespace App\Modules\Communications\Models;

use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\MessageDirection;
use App\Modules\Communications\Enums\MessageStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Mensaje de una conversación. El texto no cambia; solo su estado de entrega.
 *
 * @property int $id
 * @property string $ulid
 * @property int $conversation_id
 * @property MessageDirection $direction
 * @property MessageAuthor $author
 * @property int|null $user_id
 * @property string $body
 * @property string|null $template
 * @property string|null $dedupe_key
 * @property string|null $provider_message_id
 * @property MessageStatus $status
 * @property string|null $error
 * @property CarbonImmutable $sent_at
 * @property-read Conversation $conversation
 */
final class ConversationMessage extends Model
{
    use HasUlids;

    protected $fillable = ['conversation_id', 'direction', 'author', 'user_id', 'body', 'template', 'provider_message_id', 'status', 'sent_at'];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'direction' => MessageDirection::class,
            'author' => MessageAuthor::class,
            'status' => MessageStatus::class,
            'sent_at' => 'immutable_datetime',
        ];
    }
}
