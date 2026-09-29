<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Organization\Contracts\BranchManagerDirectory;

final class RoleBasedBranchManagerDirectory implements BranchManagerDirectory
{
    /** @return array<int, string> */
    public function candidates(): array
    {
        /** @var array<int, string> */
        return User::query()
            ->role(Role::BranchManager->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function isEligible(int $userId): bool
    {
        return array_key_exists($userId, $this->candidates());
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, string>
     */
    public function namesOf(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        /** @var array<int, string> */
        return User::query()->whereIn('id', $userIds)->pluck('name', 'id')->all();
    }
}
