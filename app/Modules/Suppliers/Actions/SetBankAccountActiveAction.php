<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\SupplierBankAccount;

/** Desactiva o reactiva una cuenta bancaria. Una cuenta inactiva no se usa para pagos nuevos. */
final class SetBankAccountActiveAction
{
    public function execute(SupplierBankAccount $account, bool $active, User $actor): SupplierBankAccount
    {
        if (! $actor->can(Permission::SupplierRecordsManage->value)) {
            throw SupplierRuleViolation::bankDataForbidden();
        }

        $account->is_active = $active;
        $account->save();

        return $account;
    }
}
