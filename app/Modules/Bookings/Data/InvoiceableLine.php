<?php

declare(strict_types=1);

namespace App\Modules\Bookings\Data;

use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;

/**
 * Un servicio del expediente separado para facturar: lo que se recauda para el proveedor (mandato),
 * lo que gana la agencia (markup y fees) y el IVA sobre ese ingreso, congelados al cotizar.
 */
final readonly class InvoiceableLine
{
    public function __construct(
        public string $itemUlid,
        public string $description,
        public ProductType $productType,
        public Money $thirdParty,
        public Money $ownIncome,
        public Money $tax,
    ) {}

    public function total(): Money
    {
        return $this->thirdParty->plus($this->ownIncome)->plus($this->tax);
    }
}
