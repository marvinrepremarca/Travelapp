<?php

declare(strict_types=1);

namespace App\Modules\Communications\Actions;

use App\Modules\Communications\Data\InboundMessage;
use App\Modules\Communications\Enums\BotStep;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\MessageDirection;
use App\Modules\Communications\Enums\MessageStatus;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Communications\Services\GuidedQuoteBot;
use App\Modules\Communications\Services\MessageOutbox;
use App\Modules\Communications\Services\PhoneNumbers;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Registra un mensaje del cliente en su conversación abierta (o abre una nueva) y, mientras el bot guiado
 * conversa, responde el siguiente paso. Idempotente por id de mensaje del proveedor.
 */
final readonly class ReceiveInboundMessageAction
{
    public function __construct(
        private PhoneNumbers $phones,
        private GuidedQuoteBot $bot,
        private MessageOutbox $outbox,
    ) {}

    public function execute(string $channelKey, InboundMessage $inbound): ?ConversationMessage
    {
        return DB::transaction(function () use ($channelKey, $inbound): ?ConversationMessage {
            if (ConversationMessage::query()->where('provider_message_id', $inbound->providerMessageId)->exists()) {
                return null;
            }

            $conversation = $this->openConversation($channelKey, $inbound);
            $message = ConversationMessage::query()->create([
                'conversation_id' => $conversation->id,
                'direction' => MessageDirection::Inbound,
                'author' => MessageAuthor::Customer,
                'body' => $inbound->body,
                'provider_message_id' => $inbound->providerMessageId,
                'status' => MessageStatus::Received,
                'sent_at' => $inbound->receivedAt,
            ]);
            $conversation->last_message_at = $inbound->receivedAt;

            if ($conversation->status === ConversationStatus::Bot) {
                $this->botTurn($conversation, $inbound);
            }

            $conversation->save();

            return $message;
        });
    }

    private function openConversation(string $channelKey, InboundMessage $inbound): Conversation
    {
        $hash = $this->phones->hash($inbound->from);
        $conversation = Conversation::query()
            ->where('contact_phone_hash', $hash)
            ->where('status', '!=', ConversationStatus::Closed)
            ->lockForUpdate()
            ->latest('id')
            ->first();
        if ($conversation instanceof Conversation) {
            return $conversation;
        }

        $conversation = new Conversation();
        $conversation->forceFill([
            'channel' => $channelKey,
            'contact_phone' => $this->phones->normalize($inbound->from),
            'contact_phone_hash' => $hash,
            'contact_name' => $inbound->profileName,
            'status' => ConversationStatus::Bot,
            'bot_step' => null,
            'bot_data' => [],
            'last_message_at' => $inbound->receivedAt,
        ])->save();

        return $conversation;
    }

    private function botTurn(Conversation $conversation, InboundMessage $inbound): void
    {
        $turn = $conversation->bot_step instanceof BotStep
            ? $this->bot->answer($conversation->bot_step, $conversation->bot_data, $inbound->body, CarbonImmutable::now(config()->string('travel.agency.timezone')))
            : $this->bot->welcome();

        $conversation->bot_step = $turn->nextStep;
        $conversation->bot_data = $turn->data;
        if (is_string($turn->data[BotStep::Name->value] ?? null)) {
            $conversation->contact_name = (string) $turn->data[BotStep::Name->value];
        }

        if ($turn->handoff) {
            $conversation->status = ConversationStatus::WaitingAgent;
        }

        $this->outbox->queue($conversation, MessageAuthor::Bot, $turn->reply);
    }
}
