<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;

/** Cambia el asesor responsable; la sucursal del cliente pasa a ser la del nuevo responsable. */
final class ReassignCustomerAction
{
    public function execute(Customer $customer, int $newOwnerId, User $actor): Customer
    {
        $owner = User::query()->visibleTo($actor)->whereKey($newOwnerId)->where('is_active', true)->first();

        if ($owner === null) {
            throw CustomerRuleViolation::ownerNotAvailable();
        }

        $customer->owner_id = $owner->id;
        $customer->branch_id = $owner->branch_id;
        $customer->save();

        return $customer;
    }
}
