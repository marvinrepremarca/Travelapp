<?php

declare(strict_types=1);

namespace App\Modules\Communications\Actions;

use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Exceptions\ConversationRuleViolation;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Identity\Models\User;

/** Cierra la conversación; si el cliente vuelve a escribir se abre una nueva con el bot. */
final class CloseConversationAction
{
    public function execute(User $agent, string $conversationUlid): Conversation
    {
        $conversation = Conversation::query()->where('ulid', $conversationUlid)->firstOrFail();
        if ($conversation->owner_id !== null && $conversation->owner_id !== $agent->id) {
            throw ConversationRuleViolation::notYours();
        }

        $conversation->status = ConversationStatus::Closed;
        $conversation->save();

        return $conversation;
    }
}
