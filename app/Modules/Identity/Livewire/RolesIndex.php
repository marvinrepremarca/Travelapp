<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

/** Listado de roles; los permisos de cada uno se editan en su propia página. */
#[Layout('components.layouts.backoffice')]
final class RolesIndex extends Component
{
    private const GUARD = 'web';

    public function mount(): void
    {
        abort_unless($this->actor()->can(Permission::RolesManage->value), 403);
    }

    public function render(): View
    {
        $title = __('identity.role_permissions.title');
        $counts = RoleModel::query()->where('guard_name', self::GUARD)->withCount(['users', 'permissions'])->get(['id', 'name'])->keyBy('name');

        return view('identity::livewire.roles-index', [
            'roles' => Role::cases(),
            'counts' => $counts,
            'locked' => Role::AgencyOwner,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
