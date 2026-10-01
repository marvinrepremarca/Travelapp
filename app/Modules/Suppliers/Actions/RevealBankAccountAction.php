<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Enums\SensitiveDataAccessType;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Suppliers\Exceptions\SupplierRuleViolation;
use App\Modules\Suppliers\Models\SupplierBankAccount;

/** Solo finanzas ve el número completo de una cuenta, y cada consulta queda auditada. */
final readonly class RevealBankAccountAction
{
    private const FIELD = 'account_number';

    public function __construct(private SensitiveDataAccessRecorder $recorder) {}

    public function execute(SupplierBankAccount $account, User $actor, string $reason): string
    {
        if (! $actor->can(Permission::FinanceAccess->value)) {
            throw SupplierRuleViolation::bankDataForbidden();
        }

        $this->recorder->record($actor, $account, self::FIELD, SensitiveDataAccessType::Viewed, $reason);

        return $account->account_number;
    }
}
