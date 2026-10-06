<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\InboundMessages;
use App\Modules\Communications\Jobs\ProcessInboundMessageJob;

final readonly class WebhookIntake implements InboundMessages
{
    public function __construct(private ChannelRegistry $channels) {}

    public function accept(string $channelKey, string $rawBody, array $headers): void
    {
        $message = $this->channels->get($channelKey)->parseInbound($rawBody, $headers);

        ProcessInboundMessageJob::dispatch($channelKey, $message);
    }
}
