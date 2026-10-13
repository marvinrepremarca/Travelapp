<?php

declare(strict_types=1);

namespace App\Modules\Identity\Livewire;

use App\Modules\Identity\Actions\UpdateRolePermissionsAction;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

/** Permisos de un rol que administra el Gerente de la agencia. */
#[Layout('components.layouts.backoffice')]
final class RolePermissions extends Component
{
    private const GUARD = 'web';

    #[Locked]
    public string $role = '';

    /** @var list<string> permisos marcados */
    public array $granted = [];

    public function mount(string $role): void
    {
        $this->authorizeRoles();
        $this->role = (Role::tryFrom($role) ?? abort(404))->value;
        $this->loadGranted();
    }

    public function save(UpdateRolePermissionsAction $update): void
    {
        $this->authorizeRoles();
        $permissions = array_values(array_filter(array_map(Permission::tryFrom(...), $this->granted)));

        $this->apply($update, $permissions, 'identity.role_permissions.saved');
    }

    public function restoreDefaults(UpdateRolePermissionsAction $update): void
    {
        $this->authorizeRoles();
        $defaults = array_values(array_filter($this->target()->permissions(), static fn(Permission $permission): bool => $permission !== Permission::RolesManage));

        $this->apply($update, $defaults, 'identity.role_permissions.restored');
    }

    public function render(): View
    {
        $target = $this->target();
        $title = __('identity.role_permissions.edit_title', ['role' => $target->label()]);
        $usersCount = RoleModel::query()->where('guard_name', self::GUARD)->where('name', $target->value)->withCount('users')->value('users_count');

        return view('identity::livewire.role-permissions', [
            'target' => $target,
            'permissions' => Permission::cases(),
            'usersCount' => (int) $usersCount,
            'isLocked' => $target === Role::AgencyOwner,
            'reserved' => Permission::RolesManage,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    /** @param list<Permission> $permissions */
    private function apply(UpdateRolePermissionsAction $update, array $permissions, string $message): void
    {
        $target = $this->target();

        try {
            $update->execute($this->actor(), $target, $permissions);
        } catch (BusinessRuleException $violation) {
            $this->addError('granted', $violation->getMessage());

            return;
        }

        session()->flash('status', __($message, ['role' => $target->label()]));
        $this->loadGranted();
    }

    private function loadGranted(): void
    {
        $model = RoleModel::query()->where('guard_name', self::GUARD)->where('name', $this->role)->with('permissions:id,name')->first();

        /** @var list<string> $names */
        $names = $model?->permissions->pluck('name')->sort()->values()->all() ?? [];
        $this->granted = $names;
    }

    private function target(): Role
    {
        return Role::from($this->role);
    }

    private function authorizeRoles(): void
    {
        abort_unless($this->actor()->can(Permission::RolesManage->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
