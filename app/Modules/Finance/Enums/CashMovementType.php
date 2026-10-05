<?php

declare(strict_types=1);

namespace App\Modules\Finance\Enums;

enum CashMovementType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return __("finance.cash_movement.{$this->value}");
    }
}
