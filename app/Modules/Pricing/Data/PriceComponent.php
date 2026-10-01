<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Data;

use App\Modules\Pricing\Enums\PriceComponentType;
use Brick\Money\Money;

final readonly class PriceComponent
{
    public function __construct(
        public PriceComponentType $type,
        public Money $amount,
        public string $description,
    ) {}
}
