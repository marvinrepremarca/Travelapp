<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Data;

use App\Modules\Shared\Enums\PassengerType;
use Brick\Money\Money;

/** Costo neto de un producto propio desglosado por tipo de pasajero. */
final readonly class NetPriceQuote
{
    /**
     * @param  array<value-of<PassengerType>, array{count: int, unit: Money}>  $lines
     */
    public function __construct(
        public string $seasonName,
        public array $lines,
        public Money $total,
    ) {}
}
