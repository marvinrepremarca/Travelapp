<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\ReceivedPayments;
use Carbon\CarbonImmutable;

/** Cobros apagada: la conciliación bancaria no propone abonos de clientes; se concilia contra lo demás. */
final class NullReceivedPayments implements ReceivedPayments
{
    public function approvedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return [];
    }

    public function find(array $ulids): array
    {
        return [];
    }
}
