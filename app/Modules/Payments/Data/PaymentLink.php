<?php

declare(strict_types=1);

namespace App\Modules\Payments\Data;

final readonly class PaymentLink
{
    public function __construct(
        public string $url,
        public string $gatewayReference,
    ) {}
}
