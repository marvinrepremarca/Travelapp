<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\CatalogProduct;

/** Activa o desactiva un producto. Inactivo no se cotiza ni se vende; nunca se borra. */
final class SetProductActiveAction
{
    public function execute(CatalogProduct $product, bool $isActive): CatalogProduct
    {
        $product->is_active = $isActive;
        $product->save();

        return $product;
    }
}
