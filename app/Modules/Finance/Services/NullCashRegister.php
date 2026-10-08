<?php

declare(strict_types=1);

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\CashRegister;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;

/** Contabilidad apagada: el abono en efectivo se registra en Cobros sin exigir ni mover una caja abierta. */
final class NullCashRegister implements CashRegister
{
    public function recordPaymentIncome(User $receiver, Money $amount, string $description, string $paymentUlid): void {}
}
