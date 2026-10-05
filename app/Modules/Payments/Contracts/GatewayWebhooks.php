<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Exceptions\PaymentRuleViolation;

/** Entrada de webhooks de pasarelas: verifica firma, registra el evento una sola vez y lo procesa en cola. */
interface GatewayWebhooks
{
    /**
     * @param  array<string, string>  $headers  encabezados en minúscula
     *
     * @throws PaymentRuleViolation firma inválida, payload malformado o pasarela desconocida
     */
    public function receive(string $gatewayKey, string $rawBody, array $headers): void;
}
