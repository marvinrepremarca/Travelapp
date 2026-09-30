<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Data\ConsentData;
use App\Modules\Crm\Data\CustomerData;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Exceptions\CustomerRuleViolation;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Services\CustomerDocumentGuard;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Crea el cliente (responsable = quien lo crea) con la evidencia de autorización de datos. */
final readonly class CreateCustomerAction
{
    public function __construct(private CustomerDocumentGuard $documents) {}

    public function execute(CustomerData $data, ConsentData $consent, User $actor): Customer
    {
        if (! $consent->dataProcessing) {
            throw CustomerRuleViolation::consentRequired();
        }

        [$number, $hash] = $this->documents->check($data);

        return DB::transaction(function () use ($data, $consent, $actor, $number, $hash): Customer {
            $customer = new Customer([
                'type' => $data->type,
                'first_name' => $data->firstName,
                'last_name' => $data->lastName,
                'legal_name' => $data->legalName,
                'birth_date' => $data->birthDate,
                'email' => $data->email === null ? null : mb_strtolower($data->email),
                'phone' => $data->phone,
                'city' => $data->city,
                'country' => $data->country,
                'notes' => $data->notes,
            ]);
            $customer->display_name = $data->displayName();
            $customer->document_type = $data->documentType;
            $customer->document_number = $number;
            $customer->document_hash = $hash;
            $customer->owner_id = $actor->id;
            $customer->branch_id = $actor->branch_id;
            $customer->save();

            $now = CarbonImmutable::now();
            $version = config()->string('travel.privacy.policy_version');

            $customer->consents()->create(['purpose' => ConsentPurpose::DataProcessing, 'granted' => true, 'channel' => $consent->channel, 'policy_version' => $version, 'recorded_by' => $actor->id, 'recorded_at' => $now]);
            $customer->consents()->create(['purpose' => ConsentPurpose::Marketing, 'granted' => $consent->marketing, 'channel' => $consent->channel, 'policy_version' => $version, 'recorded_by' => $actor->id, 'recorded_at' => $now]);

            return $customer;
        });
    }
}
