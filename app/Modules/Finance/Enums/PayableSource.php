<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

/** De dónde viene una cuenta por pagar a un proveedor. */
enum PayableSource: string
{
    /** Nace al confirmar un servicio del expediente con ese proveedor. */
    case Booking = 'booking';
    /** Obligación registrada a mano, sin expediente. */
    case Manual = 'manual';

    public function label(): string
    {
        return __("finance.payable_source.{$this->value}");
    }
}
