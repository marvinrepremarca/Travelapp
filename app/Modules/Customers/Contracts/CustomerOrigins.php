<?php

declare(strict_types=1);

namespace App\Modules\Customers\Contracts;

use App\Modules\Customers\Data\CustomerOriginFollowUp;
use App\Modules\Customers\Data\CustomerPrefill;
use App\Modules\Identity\Models\User;

/**
 * De dónde viene un cliente nuevo (p. ej. un prospecto de Comercial). El núcleo no conoce a quien lo implementa:
 * sin la capacidad que lo origina, la implementación nula hace que el formulario funcione solo (ADR-0007).
 */
interface CustomerOrigins
{
    /** Datos para prellenar el formulario; null si el origen no existe o no está en el alcance del usuario. */
    public function prefill(User $viewer, string $origin): ?CustomerPrefill;

    /** Vincula el cliente creado a su origen y dice cómo seguir; null si no hay nada que vincular. */
    public function attach(User $viewer, string $origin, int $customerId): ?CustomerOriginFollowUp;
}
