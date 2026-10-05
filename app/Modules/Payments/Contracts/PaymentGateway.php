<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Data\GatewayEvent;
use App\Modules\Payments\Data\PaymentLink;
use App\Modules\Payments\Data\PaymentLinkRequest;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;

/**
 * Puerto de pasarela de pagos (PCI DSS SAQ-A): la agencia nunca ve ni guarda datos de tarjeta; solo recibe
 * una referencia y el resultado por webhook firmado. Adaptadores: Fake hoy; Wompi, PayU o Mercado Pago después.
 */
interface PaymentGateway
{
    /** Etiqueta del contenedor con la que se registran los adaptadores de pasarela. */
    public const TAG = 'payments.gateways';

    public function key(): string;

    /** Crea el link de pago; repetir la misma `idempotencyKey` devuelve el mismo link. */
    public function createLink(PaymentLinkRequest $request): PaymentLink;

    /**
     * Verifica la firma del webhook y lo traduce a un evento del dominio.
     *
     * @param  array<string, string>  $headers
     *
     * @throws PaymentRuleViolation firma inválida o payload malformado
     */
    public function parseWebhook(string $rawBody, array $headers): GatewayEvent;
}
