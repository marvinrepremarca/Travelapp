<?php

declare(strict_types=1);

namespace App\Modules\Communications\Jobs;

use App\Modules\Communications\Actions\ReceiveInboundMessageAction;
use App\Modules\Communications\Data\InboundMessage;
use App\Modules\Shared\Enums\QueueName;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** Procesa en cola un mensaje entrante ya autenticado (el webhook responde de inmediato). */
final class ProcessInboundMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public function __construct(
        public readonly string $channelKey,
        public readonly InboundMessage $message,
    ) {
        $this->onQueue(QueueName::Default->value);
    }

    public function handle(ReceiveInboundMessageAction $receive): void
    {
        $receive->execute($this->channelKey, $this->message);
    }
}
