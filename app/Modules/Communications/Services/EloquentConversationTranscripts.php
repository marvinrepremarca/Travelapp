<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\ConversationTranscripts;
use App\Modules\Communications\Data\TranscriptLine;
use App\Modules\Communications\Models\ConversationMessage;

final readonly class EloquentConversationTranscripts implements ConversationTranscripts
{
    public function __construct(private PhoneNumbers $phones) {}

    public function forPhone(string $phone, int $limit): array
    {
        $hash = $this->phones->hash($phone);

        return array_values(ConversationMessage::query()
            ->whereHas('conversation', static fn($query) => $query->where('contact_phone_hash', $hash))
            ->latest('id')
            ->limit($limit)
            ->get(['ulid', 'direction', 'author', 'body', 'status', 'sent_at'])
            ->reverse()
            ->map(static fn(ConversationMessage $message): TranscriptLine => new TranscriptLine($message->ulid, $message->direction, $message->author, $message->body, $message->status, $message->sent_at))
            ->all());
    }
}
