<?php

declare(strict_types=1);

namespace App\Modules\Communications\Contracts;

/** Entrada única de mensajes entrantes: la usan el webhook del proveedor y el simulador de la demo. */
interface InboundMessages
{
    /**
     * Autentica con el canal y encola el procesamiento. Idempotente por id de mensaje del proveedor.
     *
     * @param  array<string, string>  $headers
     *
     * @throws \App\Modules\Communications\Exceptions\InvalidInboundMessage
     */
    public function accept(string $channelKey, string $rawBody, array $headers): void;
}
