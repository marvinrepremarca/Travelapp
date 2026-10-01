<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Models\CatalogPackageComponent;
use App\Modules\Catalog\Models\CatalogProduct;

/**
 * Agrega un producto propio a un paquete en un día del itinerario.
 * Un paquete no contiene paquetes y todos sus componentes cuestan en su misma moneda (el neto se suma sin conversión).
 */
final class AddPackageComponentAction
{
    public function execute(CatalogProduct $package, CatalogProduct $component, int $dayOffset): CatalogPackageComponent
    {
        if (! $package->isPackage()) {
            throw CatalogRuleViolation::notAPackage();
        }

        if ($component->isPackage()) {
            throw CatalogRuleViolation::nestedPackage();
        }

        if ($component->currency !== $package->currency) {
            throw CatalogRuleViolation::componentCurrencyMismatch($package->currency);
        }

        $exists = CatalogPackageComponent::query()
            ->where('package_id', $package->id)
            ->where('component_id', $component->id)
            ->where('day_offset', $dayOffset)
            ->exists();

        if ($exists) {
            throw CatalogRuleViolation::duplicatedComponent();
        }

        return CatalogPackageComponent::query()->create([
            'package_id' => $package->id,
            'component_id' => $component->id,
            'day_offset' => $dayOffset,
        ]);
    }
}
