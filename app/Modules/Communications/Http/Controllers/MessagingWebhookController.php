<?php

declare(strict_types=1);

namespace App\Modules\Communications\Http\Controllers;

use App\Modules\Communications\Contracts\InboundMessages;
use App\Modules\Communications\Exceptions\InvalidInboundMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Webhook del proveedor de WhatsApp: sin sesión ni CSRF; autentica la firma, responde rápido y procesa en cola. */
final class MessagingWebhookController
{
    public function __invoke(Request $request, string $channel, InboundMessages $inbound): JsonResponse
    {
        $headers = array_map(static fn(array $values): string => (string) ($values[0] ?? ''), array_change_key_case($request->headers->all()));

        try {
            $inbound->accept($channel, $request->getContent(), $headers);
        } catch (InvalidInboundMessage) {
            // Falla cerrado sin detalles para el emisor ni el contenido del mensaje en el log.
            Log::warning('messaging_webhook_rejected', ['channel' => $channel]);

            return new JsonResponse(['status' => 'rejected'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}
