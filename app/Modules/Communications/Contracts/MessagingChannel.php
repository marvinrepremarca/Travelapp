<?php

declare(strict_types=1);

namespace App\Modules\Communications\Contracts;

use App\Modules\Communications\Data\InboundMessage;
use App\Modules\Communications\Data\OutboundMessage;
use App\Modules\Communications\Data\SendResult;

/**
 * Puerto de mensajería (WhatsApp Business Cloud API, Twilio, 360dialog…). Los adaptadores viven en Integrations,
 * se registran con el tag y el activo se elige en config travel.communications.whatsapp_channel.
 */
interface MessagingChannel
{
    public const TAG = 'communications.channels';

    public function key(): string;

    /** Envía un mensaje. Se llama en cola, nunca dentro de una transacción. */
    public function send(OutboundMessage $message): SendResult;

    /**
     * Traduce y autentica un webhook entrante (firma del proveedor).
     *
     * @param  array<string, string>  $headers
     *
     * @throws \App\Modules\Communications\Exceptions\InvalidInboundMessage
     */
    public function parseInbound(string $rawBody, array $headers): InboundMessage;
}
