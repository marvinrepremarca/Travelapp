<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use Brick\Money\Money;

/** Lo recaudado de cada expediente en su moneda de venta: abonos aprobados menos reembolsos pagados. */
interface BookingCollections
{
    /**
     * @param  array<string, string>  $currencyByBooking  ulid del expediente → moneda de venta
     * @return array<string, Money>
     */
    public function netCollected(array $currencyByBooking): array;
}
