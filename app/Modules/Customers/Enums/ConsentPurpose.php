<?php

declare(strict_types=1);

namespace App\Modules\Customers\Enums;

/** Finalidades de tratamiento de datos (Ley 1581 de 2012). */
enum ConsentPurpose: string
{
    /** Obligatoria: prestar el servicio de viaje (reservas, emisión, contacto operativo). */
    case DataProcessing = 'data_processing';

    /** Opcional: ofertas y campañas. */
    case Marketing = 'marketing';

    public function label(): string
    {
        return __("customers.consent_purpose.{$this->value}");
    }
}
