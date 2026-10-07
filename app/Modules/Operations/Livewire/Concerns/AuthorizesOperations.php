<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire\Concerns;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Support\Facades\Auth;

trait AuthorizesOperations
{
    protected function authorizeOperations(): void
    {
        abort_unless($this->actor()->can(Permission::OperationsManage->value), 403);
    }

    protected function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
