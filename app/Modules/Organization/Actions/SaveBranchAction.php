<?php

declare(strict_types=1);

namespace App\Modules\Organization\Actions;

use App\Modules\Organization\Contracts\BranchManagerDirectory;
use App\Modules\Organization\Data\BranchData;
use App\Modules\Organization\Exceptions\InvalidBranchManager;
use App\Modules\Organization\Models\Branch;

/** Crea (si no recibe sucursal) o actualiza una sucursal. */
final readonly class SaveBranchAction
{
    public function __construct(private BranchManagerDirectory $managers) {}

    public function execute(BranchData $data, ?Branch $branch = null): Branch
    {
        if ($data->managerId !== null && ! $this->managers->isEligible($data->managerId)) {
            throw InvalidBranchManager::notEligible();
        }

        if (! $branch instanceof Branch) {
            $branch = new Branch();
            $branch->is_active = true;
        }

        $branch->fill([
            'code' => mb_strtoupper($data->code),
            'name' => $data->name,
            'city' => $data->city,
            'address' => $data->address,
            'phone' => $data->phone,
            'email' => $data->email,
            'timezone' => $data->timezone,
        ]);
        $branch->manager_id = $data->managerId;
        $branch->save();

        return $branch;
    }
}
