<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Data;

use App\Modules\Invoicing\Enums\EInvoiceStatus;

/** Respuesta del proveedor de facturación electrónica (CUFE/CUDE como referencia cuando aplique). */
final readonly class EInvoiceResult
{
    public function __construct(
        public EInvoiceStatus $status,
        public ?string $reference = null,
        public ?string $message = null,
    ) {}
}
