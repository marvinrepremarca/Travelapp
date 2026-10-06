<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum CashMovementType: string
{
    case Income = 'income';
    case Expense = 'expense';
    /** Consignación del efectivo en la cuenta bancaria de la agencia (se concilia con el extracto). */
    case BankDeposit = 'bank_deposit';

    /** @return list<self> */
    public static function outflows(): array
    {
        return [self::Expense, self::BankDeposit];
    }

    public function label(): string
    {
        return __("finance.cash_movement.{$this->value}");
    }
}
