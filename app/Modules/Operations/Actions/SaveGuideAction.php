<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Operations\Models\Guide;

/** Crea o edita un guía. */
final class SaveGuideAction
{
    /** @param  array{name: string, phone: string, languages: ?string, license_number: ?string, is_active: bool}  $data */
    public function execute(array $data, ?Guide $guide = null): Guide
    {
        $guide ??= new Guide();
        $guide->fill($data)->save();

        return $guide;
    }
}
