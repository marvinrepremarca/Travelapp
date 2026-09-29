<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Models\Branch;

/** Activa o desactiva una sucursal; nunca se borra para no romper el historial. */
final class SetBranchActiveAction
{
    public function execute(Branch $branch, bool $active): Branch
    {
        $branch->is_active = $active;
        $branch->save();

        return $branch;
    }
}
