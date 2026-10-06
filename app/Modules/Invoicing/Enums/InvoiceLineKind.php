<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Enums;

/** Naturaleza de una línea facturada: la agencia como mandataria del proveedor o su ingreso propio. */
enum InvoiceLineKind: string
{
    /** Recaudo para terceros (mandato): no es ingreso de la agencia ni causa su IVA. */
    case ThirdParty = 'third_party';

    /** Ingreso propio de la agencia (markup, fees, producto propio): base del IVA. */
    case OwnIncome = 'own_income';

    public function label(): string
    {
        return __("invoicing.line_kind.{$this->value}");
    }
}
