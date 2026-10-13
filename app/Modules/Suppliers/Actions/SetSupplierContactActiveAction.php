<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Models\SupplierContact;

/** Desactiva o reactiva un contacto. Nunca se borra: queda como historial. */
final class SetSupplierContactActiveAction
{
    public function execute(SupplierContact $contact, bool $active): SupplierContact
    {
        $contact->is_active = $active;
        $contact->save();

        return $contact;
    }
}
