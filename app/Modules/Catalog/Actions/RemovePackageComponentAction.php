<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\CatalogPackageComponent;

/** Quita un componente del paquete. Queda en la auditoría. */
final class RemovePackageComponentAction
{
    public function execute(CatalogPackageComponent $component): void
    {
        $component->delete();
    }
}
