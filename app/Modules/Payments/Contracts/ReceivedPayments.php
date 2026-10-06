<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Data\ReceivedPayment;
use Carbon\CarbonImmutable;

/** Abonos aprobados que pasan por el banco (no el efectivo, que entra por caja). */
interface ReceivedPayments
{
    /**
     * @param  CarbonImmutable  $from  instante UTC inclusivo
     * @param  CarbonImmutable  $until  instante UTC exclusivo
     * @return list<ReceivedPayment>
     */
    public function approvedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): array;

    /**
     * @param  list<string>  $ulids
     * @return list<ReceivedPayment>
     */
    public function find(array $ulids): array;
}
