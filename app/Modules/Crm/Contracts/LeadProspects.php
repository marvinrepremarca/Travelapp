<?php

declare(strict_types=1);

namespace App\Modules\Crm\Contracts;

use App\Modules\Crm\Data\LeadProspect;
use App\Modules\Identity\Models\User;

/** Datos de contacto de un lead abierto para cotizarle sin registrarlo como cliente (ADR-0007: nulo con Comercial apagada). */
interface LeadProspects
{
    /** Null si el lead no existe, no es visible para el usuario o ya está cerrado. */
    public function prospect(User $viewer, string $leadUlid): ?LeadProspect;
}
