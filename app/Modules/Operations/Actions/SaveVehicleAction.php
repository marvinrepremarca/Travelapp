<?php

declare(strict_types=1);

namespace App\Modules\Operations\Actions;

use App\Modules\Operations\Models\Vehicle;

/** Crea o edita un vehículo (la placa se guarda en mayúsculas). */
final class SaveVehicleAction
{
    /** @param  array{plate: string, description: string, capacity: int, is_active: bool}  $data */
    public function execute(array $data, ?Vehicle $vehicle = null): Vehicle
    {
        $vehicle ??= new Vehicle();
        $vehicle->fill(['plate' => mb_strtoupper(trim($data['plate'])), 'description' => $data['description'], 'capacity' => $data['capacity'], 'is_active' => $data['is_active']])->save();

        return $vehicle;
    }
}
