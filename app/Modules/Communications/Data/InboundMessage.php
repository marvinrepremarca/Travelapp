<?php

declare(strict_types=1);

namespace App\Modules\Communications\Data;

use Carbon\CarbonImmutable;

/** Mensaje recibido del cliente, ya traducido del formato del proveedor. */
final readonly class InboundMessage
{
    public function __construct(
        public string $providerMessageId,
        public string $from,
        public string $body,
        public CarbonImmutable $receivedAt,
        public ?string $profileName = null,
    ) {}
}
