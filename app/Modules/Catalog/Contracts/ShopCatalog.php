<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Contracts;

use App\Modules\Catalog\Data\ShopProduct;
use Carbon\CarbonImmutable;

/** Productos propios publicables en la tienda B2C: activos, con salidas abiertas y cupo en la ventana indicada. */
interface ShopCatalog
{
    /** @return list<ShopProduct> */
    public function available(CarbonImmutable $from, CarbonImmutable $until): array;

    public function product(string $productUlid, CarbonImmutable $from, CarbonImmutable $until): ?ShopProduct;
}
