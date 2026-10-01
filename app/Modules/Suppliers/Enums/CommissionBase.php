<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Enums;

/** Sobre qué tarifa se calcula la comisión pactada. */
enum CommissionBase: string
{
    /** Tarifa pública del proveedor (comisionable). */
    case Gross = 'gross';

    /** Tarifa neta (incentivo sobre el neto). */
    case Net = 'net';

    public function label(): string
    {
        return __("suppliers.commission_base.{$this->value}");
    }
}
