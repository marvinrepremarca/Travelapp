<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Enums\ConsentChannel;
use App\Modules\Customers\Enums\ConsentPurpose;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\CustomerConsent;
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
