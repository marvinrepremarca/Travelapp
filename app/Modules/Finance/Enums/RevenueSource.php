<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

/** De dónde viene un ingreso contable. */
enum RevenueSource: string
{
    /** Reconocido al confirmar un servicio de un expediente (base de causación). */
    case BookingItem = 'booking_item';
    /** Venta que no pasó por el sistema, registrada por finanzas. */
    case Manual = 'manual';

    public function label(): string
    {
        return __("finance.revenue_source.{$this->value}");
    }
}
