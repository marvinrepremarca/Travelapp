<?php

declare(strict_types=1);

namespace App\Modules\Identity\Data;

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\VisibilityScope;

final readonly class UserData
{
    public function __construct(
        public string $name,
        public string $email,
        public Role $role,
        public ?int $branchId,
        public VisibilityScope $scope,
    ) {}
}
