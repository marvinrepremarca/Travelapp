<?php

declare(strict_types=1);

namespace App\Modules\Crm\Services;

use App\Modules\Crm\Actions\SaveLeadAction;
use App\Modules\Crm\Contracts\LeadIntake;
use App\Modules\Crm\Data\LeadData;
use App\Modules\Crm\Models\Lead;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;

final readonly class ActionLeadIntake implements LeadIntake
{
    public function __construct(private SaveLeadAction $save) {}

    public function register(LeadData $data, User $owner): string
    {
        return $this->save->execute($data, $owner)->ulid;
    }

    public function customerOf(string $leadUlid): ?string
    {
        $customerId = Lead::query()->where('ulid', $leadUlid)->value('customer_id');

        return $customerId === null ? null : Customer::query()->whereKey($customerId)->value('ulid');
    }
}
