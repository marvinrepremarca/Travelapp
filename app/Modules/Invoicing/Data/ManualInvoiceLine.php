<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Data;

use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;

/** Línea de una factura emitida a mano: ingreso propio de la agencia (con IVA) o recaudo para un tercero (sin IVA). */
final readonly class ManualInvoiceLine
{
    public function __construct(
        public string $description,
        public InvoiceLineKind $kind,
        public Money $amount,
        public ?ProductType $productType,
    ) {}
}
