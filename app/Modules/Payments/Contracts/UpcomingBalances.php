<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Data\BalanceDue;
use Carbon\CarbonImmutable;

/** Expedientes con saldo que vence en una fecha (el saldo vence N días ⚙ antes del primer servicio). */
interface UpcomingBalances
{
    /** @return list<BalanceDue> */
    public function dueOn(CarbonImmutable $dueDate): array;
}
