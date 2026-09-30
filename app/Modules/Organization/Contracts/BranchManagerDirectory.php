<?php

declare(strict_types=1);

namespace App\Modules\Organization\Contracts;

/**
 * Usuarios que pueden dirigir una sucursal. Lo implementa el módulo Identity,
 * así Organization no depende de él (inversión de dependencias).
 */
interface BranchManagerDirectory
{
    /** @return array<int, string> id => nombre, solo usuarios activos con el rol de director de sucursal */
    public function candidates(): array;

    public function isEligible(int $userId): bool;

    /**
     * @param  list<int>  $userIds
     * @return array<int, string> id => nombre
     */
    public function namesOf(array $userIds): array;
}
