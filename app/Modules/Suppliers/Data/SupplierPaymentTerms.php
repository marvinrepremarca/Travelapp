<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Data;

use App\Modules\Suppliers\Enums\PaymentTerms;

/** Condiciones de pago pactadas con un proveedor, para calcular vencimientos de cuentas por pagar. */
final readonly class SupplierPaymentTerms
{
    public function __construct(
        public int $supplierId,
        public string $tradeName,
        public PaymentTerms $terms,
        public int $days,
        public string $currency,
    ) {}
}
