<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Models\CatalogProduct;

/** Crea o actualiza un producto propio. Los productos nuevos quedan activos. */
final class SaveProductAction
{
    public function execute(ProductData $data, ?CatalogProduct $product = null): CatalogProduct
    {
        $isNew = ! $product instanceof CatalogProduct;
        $product ??= new CatalogProduct();
        $product->fill([
            'code' => mb_strtoupper(trim($data->code)),
            'name' => $data->name,
            'product_type' => $data->productType,
            'description' => $data->description,
            'destination_country' => mb_strtoupper($data->destinationCountry),
            'destination_city' => $data->destinationCity,
            'timezone' => $data->timezone,
            'duration_minutes' => $data->durationMinutes,
            'supplier_id' => $data->supplierId,
            'currency' => mb_strtoupper($data->currency),
        ]);

        if ($isNew) {
            $product->is_active = true;
        }

        $product->save();

        return $product;
    }
}
