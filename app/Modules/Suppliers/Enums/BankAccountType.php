<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Enums;

enum BankAccountType: string
{
    case Savings = 'savings';
    case Checking = 'checking';

    public function label(): string
    {
        return __("suppliers.account_type.{$this->value}");
    }
}
