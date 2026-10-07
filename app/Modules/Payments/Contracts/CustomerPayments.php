<?php

declare(strict_types=1);

namespace App\Modules\Payments\Contracts;

use App\Modules\Payments\Data\BalanceSummary;

/** Estado de cuenta y pago en línea iniciado por el propio cliente (portal del viajero). */
interface CustomerPayments
{
    public function statement(string $bookingUlid): BalanceSummary;

    /**
     * URL de pago del saldo por cobrar: reutiliza un link vigente por el mismo valor o crea uno nuevo.
     * Null si no hay nada por cobrar.
     */
    public function payBalanceUrl(string $bookingUlid): ?string;
}
