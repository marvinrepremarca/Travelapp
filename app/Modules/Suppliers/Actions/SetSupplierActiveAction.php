<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Models\Supplier;

/** Activa o desactiva un proveedor; nunca se borra porque tiene historial de compras. */
final class SetSupplierActiveAction
{
    public function execute(Supplier $supplier, bool $active): Supplier
    {
        $supplier->is_active = $active;
        $supplier->save();

        return $supplier;
    }
}
