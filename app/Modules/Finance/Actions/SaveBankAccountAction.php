<?php

declare(strict_types=1);

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Data\BankAccountData;
use App\Modules\Finance\Models\AgencyBankAccount;
use App\Modules\Finance\Models\BankStatement;

/** Crea o edita una cuenta bancaria de la agencia. La moneda no cambia después de cargar extractos. */
final class SaveBankAccountAction
{
    public function execute(BankAccountData $data, ?AgencyBankAccount $account = null): AgencyBankAccount
    {
        $account ??= new AgencyBankAccount();
        $hasStatements = $account->exists && BankStatement::query()->where('agency_bank_account_id', $account->id)->exists();

        $account->fill([
            'name' => $data->name,
            'bank_name' => $data->bankName,
            'account_last_digits' => $data->accountLastDigits,
            'currency' => $hasStatements ? $account->currency : $data->currency,
            'statement_format' => $data->format->toArray(),
            'is_active' => $data->isActive,
        ]);
        $account->save();

        return $account;
    }
}
