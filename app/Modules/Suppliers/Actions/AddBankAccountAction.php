<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Suppliers\Enums\BankAccountType;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierBankAccount;

/** Solo finanzas registra cuentas bancarias: un cambio de cuenta es un vector típico de fraude. */
final class AddBankAccountAction
{
    public function execute(Supplier $supplier, string $bankName, BankAccountType $type, string $number, string $holderName, string $currency, User $actor): SupplierBankAccount
    {
        if (! $actor->can(Permission::FinanceAccess->value)) {
            throw SupplierRuleViolation::bankDataForbidden();
        }

        return $supplier->bankAccounts()->create([
            'bank_name' => $bankName,
            'account_type' => $type,
            'account_number' => (string) preg_replace('/[\s.\-]/', '', $number),
            'holder_name' => $holderName,
            'currency' => mb_strtoupper($currency),
        ]);
    }
}
