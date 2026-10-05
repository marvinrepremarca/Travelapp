<?php

declare(strict_types=1);

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;

/** Caja de la sucursal para otros módulos: un abono en efectivo entra a la caja abierta de quien lo recibe. */
interface CashRegister
{
    /**
     * Registra la entrada en la caja abierta de la sucursal del usuario (en la misma transacción que el abono).
     *
     * @throws FinanceRuleViolation sin sucursal, sin caja abierta o en otra moneda
     */
    public function recordPaymentIncome(User $receiver, Money $amount, string $description, string $paymentUlid): void;
}
