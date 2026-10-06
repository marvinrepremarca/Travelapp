<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\MessageDirection;
use App\Modules\Communications\Enums\MessageStatus;
use App\Modules\Communications\Jobs\DeliverMessageJob;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use Carbon\CarbonImmutable;

/** Registra un mensaje saliente y encola su entrega después de confirmar la transacción. */
final class MessageOutbox
{
    public function queue(Conversation $conversation, MessageAuthor $author, string $body, ?int $userId = null, ?string $template = null): ConversationMessage
    {
        $now = CarbonImmutable::now();
        $message = ConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => MessageDirection::Outbound,
            'author' => $author,
            'user_id' => $userId,
            'body' => $body,
            'template' => $template,
            'status' => MessageStatus::Queued,
            'sent_at' => $now,
        ]);

        $conversation->last_message_at = $now;
        $conversation->save();

        DeliverMessageJob::dispatch($message->ulid)->afterCommit();

        return $message;
    }
}
