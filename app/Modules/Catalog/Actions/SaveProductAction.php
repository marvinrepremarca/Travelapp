<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogPackageComponent;
use App\Modules\Catalog\Models\CatalogProduct;

/** Crea o actualiza un producto propio. Los productos nuevos quedan activos. */
final class SaveProductAction
{
    public function execute(ProductData $data, ?CatalogProduct $product = null): CatalogProduct
    {
        $isNew = ! $product instanceof CatalogProduct;

        $changesComposition = ! $isNew && ($product->product_type !== $data->productType || $product->currency !== mb_strtoupper($data->currency));
        if ($changesComposition && $this->isLinkedToPackages($product)) {
            throw CatalogRuleViolation::productTypeLocked();
        }

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

    /** Cambiar tipo o moneda rompería la composición: un componente pasaría a ser paquete o dejaría de sumar en la moneda del paquete. */
    private function isLinkedToPackages(CatalogProduct $product): bool
    {
        return CatalogPackageComponent::query()
            ->where('package_id', $product->id)
            ->orWhere('component_id', $product->id)
            ->exists();
    }
}
