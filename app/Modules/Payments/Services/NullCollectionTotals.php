<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\CollectionTotals;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

/** Cobros apagada: nada cobrado por el sistema. */
final class NullCollectionTotals implements CollectionTotals
{
    public function netCollectedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): Money
    {
        return Money::zero($currency);
    }
}
