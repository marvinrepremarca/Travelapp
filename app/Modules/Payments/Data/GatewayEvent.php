<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

use App\Modules\Payments\Enums\PaymentStatus;

/** Resultado de un pago informado por la pasarela, ya verificado y normalizado. */
final readonly class GatewayEvent
{
    public function __construct(
        public string $eventId,
        public string $gatewayReference,
        public PaymentStatus $outcome,
    ) {}
}
