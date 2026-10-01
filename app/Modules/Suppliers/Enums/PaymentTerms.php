<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Enums;

enum PaymentTerms: string
{
    /** Se paga X días antes del servicio. */
    case Prepaid = 'prepaid';

    /** Se paga X días después de la factura del proveedor. */
    case Credit = 'credit';

    public function label(): string
    {
        return __("suppliers.payment_terms.{$this->value}");
    }
}
