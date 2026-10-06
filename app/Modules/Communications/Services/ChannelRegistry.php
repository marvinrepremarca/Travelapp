<?php

declare(strict_types=1);

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\MessagingChannel;
use App\Modules\Communications\Exceptions\InvalidInboundMessage;
use Illuminate\Contracts\Container\Container;

/** Canal de mensajería por clave (cambiar de proveedor = cambiar .env). */
final readonly class ChannelRegistry
{
    public function __construct(private Container $container) {}

    public function active(): MessagingChannel
    {
        return $this->get(config()->string('travel.communications.whatsapp_channel'));
    }

    public function get(string $key): MessagingChannel
    {
        foreach ($this->container->tagged(MessagingChannel::TAG) as $channel) {
            if ($channel instanceof MessagingChannel && $channel->key() === $key) {
                return $channel;
            }
        }

        throw InvalidInboundMessage::because('unknown_channel');
    }
}
