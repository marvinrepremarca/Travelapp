<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Suppliers\Enums\BankAccountType;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\SupplierBankAccount;

/**
 * Corrige una cuenta existente. El número se cambia solo si llega uno nuevo (nunca se muestra el actual).
 * Quien corrige necesita el permiso de registros; crear cuentas sigue siendo exclusivo de finanzas.
 */
final class UpdateBankAccountAction
{
    public function execute(SupplierBankAccount $account, string $bankName, BankAccountType $type, ?string $number, string $holderName, string $currency, User $actor): SupplierBankAccount
    {
        if (! $actor->can(Permission::SupplierRecordsManage->value)) {
            throw SupplierRuleViolation::bankDataForbidden();
        }

        $account->fill([
            'bank_name' => $bankName,
            'account_type' => $type,
            'holder_name' => $holderName,
            'currency' => mb_strtoupper($currency),
        ]);

        if ($number !== null && $number !== '') {
            $account->account_number = (string) preg_replace('/[\s.\-]/', '', $number);
        }

        $account->save();

        return $account;
    }
}
