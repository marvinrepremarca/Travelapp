<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\UpcomingBalances;
use Carbon\CarbonImmutable;

/** Cobros apagada: no hay saldos que recordar. */
final class NullUpcomingBalances implements UpcomingBalances
{
    public function dueOn(CarbonImmutable $dueDate): array
    {
        return [];
    }
}
