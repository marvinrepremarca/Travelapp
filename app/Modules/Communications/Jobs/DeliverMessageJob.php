<?php

declare(strict_types=1);

namespace App\Modules\Communications\Jobs;

use App\Modules\Communications\Data\OutboundMessage;
use App\Modules\Communications\Enums\MessageStatus;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Communications\Services\ChannelRegistry;
use App\Modules\Shared\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Entrega un mensaje saliente por el canal activo. Idempotente: solo envía lo que sigue en cola. */
final class DeliverMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public function __construct(public readonly string $messageUlid)
    {
        $this->onQueue(QueueName::Default->value);
        $this->tries = config()->integer('travel.communications.send_tries');
        /** @var list<int> $backoff */
        $backoff = config()->array('travel.communications.send_backoff_seconds');
        $this->backoff = $backoff;
    }

    public function handle(ChannelRegistry $channels): void
    {
        $message = ConversationMessage::query()->with('conversation:id,channel,contact_phone')->where('ulid', $this->messageUlid)->firstOrFail();
        if ($message->status !== MessageStatus::Queued) {
            return;
        }

        $result = $channels->get($message->conversation->channel)->send(new OutboundMessage($message->ulid, $message->conversation->contact_phone, $message->body, $message->template));

        $message->status = $result->accepted ? MessageStatus::Sent : MessageStatus::Failed;
        $message->provider_message_id = $result->providerMessageId;
        $message->error = $result->error;
        $message->save();
    }
}
