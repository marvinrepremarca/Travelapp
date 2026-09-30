<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\CustomerConsent;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;

/** Registra una nueva autorización o revocación; el historial nunca se modifica. */
final class RecordConsentAction
{
    public function execute(Customer $customer, ConsentPurpose $purpose, bool $granted, ConsentChannel $channel, User $actor): CustomerConsent
    {
        return $customer->consents()->create([
            'purpose' => $purpose,
            'granted' => $granted,
            'channel' => $channel,
            'policy_version' => config()->string('travel.privacy.policy_version'),
            'recorded_by' => $actor->id,
            'recorded_at' => CarbonImmutable::now(),
        ]);
    }
}
