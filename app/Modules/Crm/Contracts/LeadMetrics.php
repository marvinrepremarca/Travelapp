<?php

declare(strict_types=1);

namespace App\Modules\Crm\Contracts;

use App\Modules\Crm\Data\OpenLead;
use App\Modules\Shared\Contracts\ScopedViewer;
use Carbon\CarbonImmutable;

/** Indicadores de la capacidad Comercial para los tableros (ADR-0007). Con Comercial apagada todo vale cero. */
interface LeadMetrics
{
    /** Prospectos creados en [from, until) dentro del alcance del usuario, opcionalmente de un asesor. */
    public function createdBetween(ScopedViewer $viewer, CarbonImmutable $from, CarbonImmutable $until, ?int $ownerId): int;

    /**
     * Prospectos sin atender del asesor, los más antiguos primero.
     *
     * @return list<OpenLead>
     */
    public function openFor(int $ownerId, int $limit): array;
}
