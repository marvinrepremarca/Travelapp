<?php

declare(strict_types=1);

namespace App\Modules\Payments\Enums;

enum PaymentMethod: string
{
    /** Link tokenizado de la pasarela: se aprueba por webhook firmado. */
    case OnlineLink = 'online_link';
    /** Transferencia o consignación con comprobante: la valida finanzas. */
    case BankTransfer = 'bank_transfer';
    /** Efectivo recibido en la sucursal: queda aprobado al registrarse. */
    case Cash = 'cash';

    public function label(): string
    {
        return __("payments.method.{$this->value}");
    }

    /** El estado inicial depende de quién confirma el dinero. */
    public function initialStatus(): PaymentStatus
    {
        return $this === self::Cash ? PaymentStatus::Approved : PaymentStatus::Pending;
    }
}
