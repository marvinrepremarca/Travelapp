<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Enums;

/** Componentes trazables del precio (pricing-engine): cada uno se guarda por separado. */
enum PriceComponentType: string
{
    case SupplierNet = 'supplier_net';
    case Markup = 'markup';
    case ServiceFee = 'service_fee';
    case Tax = 'tax';

    public function label(): string
    {
        return __("pricing.component.{$this->value}");
    }

    /** Lo que gana la agencia (base del IVA cuando actúa como intermediaria). */
    public function isAgencyIncome(): bool
    {
        return in_array($this, [self::Markup, self::ServiceFee], true);
    }
}
