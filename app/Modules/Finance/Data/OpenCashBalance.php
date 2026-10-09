<?php

declare(strict_types=1);

namespace App\Modules\Finance\Data;

use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Caja abierta de una sucursal con el efectivo que debería tener. */
final readonly class OpenCashBalance
{
    public function __construct(
        public string $branchName,
        public Money $expected,
        public CarbonImmutable $openedAt,
    ) {}
}
