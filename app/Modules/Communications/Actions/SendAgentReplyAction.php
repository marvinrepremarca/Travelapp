<?php

declare(strict_types=1);

namespace App\Modules\Communications\Actions;

use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Exceptions\ConversationRuleViolation;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Communications\Services\MessageOutbox;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/** El asesor a cargo responde al cliente; el mensaje sale por el canal en cola. */
final readonly class SendAgentReplyAction
{
    public function __construct(private MessageOutbox $outbox) {}

    public function execute(User $agent, string $conversationUlid, string $body): ConversationMessage
    {
        return DB::transaction(function () use ($agent, $conversationUlid, $body): ConversationMessage {
            $conversation = Conversation::query()->where('ulid', $conversationUlid)->lockForUpdate()->firstOrFail();
            if (! $conversation->status->isOpen()) {
                throw ConversationRuleViolation::closed();
            }

            if ($conversation->owner_id !== $agent->id) {
                throw ConversationRuleViolation::notYours();
            }

            return $this->outbox->queue($conversation, MessageAuthor::Agent, $body, $agent->id);
        });
    }
}
