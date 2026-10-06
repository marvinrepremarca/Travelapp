<?php

declare(strict_types=1);

namespace App\Modules\Communications\Data;

/** Mensaje a enviar. El id local sirve como clave de idempotencia ante el proveedor. */
final readonly class OutboundMessage
{
    public function __construct(
        public string $localId,
        public string $to,
        public string $body,
        public ?string $template = null,
    ) {}
}
