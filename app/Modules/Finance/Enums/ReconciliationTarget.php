<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

/** Movimiento del sistema que se cruza con una línea del banco. */
enum ReconciliationTarget: string
{
    /** Abono de cliente por transferencia o pago en línea (entra al banco). */
    case CustomerPayment = 'customer_payment';

    /** Liquidación a un proveedor (sale del banco). */
    case SupplierSettlement = 'supplier_settlement';

    /** Consignación del efectivo de caja (entra al banco). */
    case CashDeposit = 'cash_deposit';

    public function label(): string
    {
        return __("finance.reconciliation_target.{$this->value}");
    }

    /** Entradas al banco: valor positivo en el extracto. */
    public function isInflow(): bool
    {
        return $this !== self::SupplierSettlement;
    }
}
