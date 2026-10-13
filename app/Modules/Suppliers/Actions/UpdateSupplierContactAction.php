<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Models\SupplierContact;

/** Corrige los datos de un contacto. Los cambios quedan auditados por el modelo. */
final class UpdateSupplierContactAction
{
    public function execute(SupplierContact $contact, string $name, ?string $position, ?string $email, ?string $phone): SupplierContact
    {
        $contact->fill([
            'name' => $name,
            'position' => $position,
            'email' => $email === null ? null : mb_strtolower($email),
            'phone' => $phone,
        ])->save();

        return $contact;
    }
}
