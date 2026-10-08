<?php

declare(strict_types=1);

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Data\CustomerData;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Services\CustomerDocumentGuard;

/** Actualiza los datos del cliente. El responsable y la sucursal cambian solo con ReassignCustomerAction. */
final readonly class UpdateCustomerAction
{
    public function __construct(private CustomerDocumentGuard $documents) {}

    public function execute(Customer $customer, CustomerData $data): Customer
    {
        [$number, $hash] = $this->documents->check($data, $customer);

        $customer->fill([
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
        $customer->document_hash = $hash;

        // Solo se reescribe el cifrado si el documento cambió, para no registrar cambios falsos.
        if ($customer->isDirty('document_hash') || $customer->isDirty('document_type')) {
            $customer->document_number = $number;
        }

        $customer->save();

        return $customer;
    }
}
