<?php

declare(strict_types=1);

namespace App\Modules\Crm\Actions;

use App\Modules\Crm\Data\TravelerData;
use App\Modules\Crm\Enums\DocumentType;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Traveler;
use App\Modules\Crm\Services\PersonalDataHasher;

/** Crea o actualiza un pasajero del cliente. Un pasaporte vacío en edición conserva el actual. */
final readonly class SaveTravelerAction
{
    public const PASSPORT_HASH_SCOPE = 'traveler_passport';

    public function __construct(private PersonalDataHasher $hasher) {}

    public function execute(Customer $customer, TravelerData $data, ?Traveler $traveler = null): Traveler
    {
        $traveler ??= new Traveler();
        $traveler->customer_id = $customer->id;
        $traveler->fill([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'gender' => $data->gender,
            'birth_date' => $data->birthDate,
            'nationality' => mb_strtoupper($data->nationality),
            'passport_country' => $data->passportCountry === null ? null : mb_strtoupper($data->passportCountry),
            'passport_expires_on' => $data->passportExpiresOn?->toDateString(),
        ]);

        if ($data->passportNumber !== null && $data->passportNumber !== '') {
            $number = DocumentType::Passport->normalize($data->passportNumber);
            $hash = $this->hasher->hash(self::PASSPORT_HASH_SCOPE, $number);

            if ($hash !== $traveler->passport_hash) {
                $traveler->passport_number = $number;
                $traveler->passport_hash = $hash;
            }
        }

        $traveler->save();

        return $traveler;
    }
}
