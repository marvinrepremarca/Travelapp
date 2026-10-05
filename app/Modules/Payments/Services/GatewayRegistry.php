<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\PaymentGateway;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use Illuminate\Contracts\Container\Container;

/** Pasarelas registradas por etiqueta; la activa se elige con `TRAVEL_PAYMENT_GATEWAY` (cambiar de pasarela = cambiar .env). */
final readonly class GatewayRegistry
{
    public function __construct(private Container $container) {}

    public function active(): PaymentGateway
    {
        return $this->get(config()->string('travel.payments.gateway'));
    }

    public function get(string $key): PaymentGateway
    {
        foreach ($this->container->tagged(PaymentGateway::TAG) as $gateway) {
            if ($gateway instanceof PaymentGateway && $gateway->key() === $key) {
                return $gateway;
            }
        }

        throw PaymentRuleViolation::unknownGateway($key);
    }
}
