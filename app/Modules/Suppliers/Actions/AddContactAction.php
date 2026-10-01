<?php

declare(strict_types=1);

namespace App\Modules\Suppliers\Actions;

use App\Modules\Suppliers\Models\Supplier;
use App\Modules\Suppliers\Models\SupplierContact;

final class AddContactAction
{
    public function execute(Supplier $supplier, string $name, ?string $position, ?string $email, ?string $phone): SupplierContact
    {
        return $supplier->contacts()->create([
            'name' => $name,
            'position' => $position,
            'email' => $email === null ? null : mb_strtolower($email),
            'phone' => $phone,
        ]);
    }
}
