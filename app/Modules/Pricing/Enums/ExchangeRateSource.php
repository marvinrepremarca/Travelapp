<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Enums;

enum ExchangeRateSource: string
{
    /** Tasa oficial (TRM para USD/COP) obtenida automáticamente. */
    case Official = 'official';

    /** Registrada a mano por finanzas (respaldo o monedas sin fuente automática). */
    case Manual = 'manual';

    public function label(): string
    {
        return __("pricing.rate_source.{$this->value}");
    }
}
