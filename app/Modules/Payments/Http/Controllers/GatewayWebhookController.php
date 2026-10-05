<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Controllers;

use App\Modules\Payments\Contracts\GatewayWebhooks;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Webhook de pasarelas: sin sesión ni CSRF; la autenticidad la da la firma. Responde rápido y procesa en cola. */
final class GatewayWebhookController
{
    public function __invoke(Request $request, string $gateway, GatewayWebhooks $webhooks): JsonResponse
    {
        $headers = array_map(static fn(array $values): string => (string) ($values[0] ?? ''), array_change_key_case($request->headers->all()));

        try {
            $webhooks->receive($gateway, $request->getContent(), $headers);
        } catch (PaymentRuleViolation $violation) {
            // Falla cerrado y sin detalles para el emisor; el motivo queda en el log (sin el payload).
            Log::warning('payment_webhook_rejected', ['gateway' => $gateway, 'reason' => $violation->errorCode()]);

            return new JsonResponse(['status' => 'rejected'], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(['status' => 'accepted'], Response::HTTP_ACCEPTED);
    }
}
