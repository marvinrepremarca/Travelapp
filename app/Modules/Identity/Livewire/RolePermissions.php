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
use Livewire\Component;
use Spatie\Permission\Models\Role as RoleModel;

/** Matriz de roles y permisos que administra el Gerente de la agencia. */
#[Layout('components.layouts.backoffice')]
final class RolePermissions extends Component
{
    private const GUARD = 'web';

    /** @var array<string, list<string>> rol → permisos marcados */
    public array $granted = [];

    public function mount(): void
    {
        $this->authorizeRoles();
        $this->loadGranted();
    }

    public function save(string $role, UpdateRolePermissionsAction $update): void
    {
        $this->authorizeRoles();
        $target = Role::from($role);
        $permissions = array_values(array_filter(array_map(
            Permission::tryFrom(...),
            $this->granted[$role] ?? [],
        )));

        try {
            $update->execute($this->actor(), $target, $permissions);
        } catch (BusinessRuleException $violation) {
            $this->addError("granted.{$role}", $violation->getMessage());

            return;
        }

        session()->flash('status', __('identity.role_permissions.saved', ['role' => $target->label()]));
        $this->loadGranted();
    }

    public function restoreDefaults(string $role, UpdateRolePermissionsAction $update): void
    {
        $this->authorizeRoles();
        $target = Role::from($role);
        $defaults = array_values(array_filter($target->permissions(), static fn(Permission $permission): bool => $permission !== Permission::RolesManage));

        try {
            $update->execute($this->actor(), $target, $defaults);
        } catch (BusinessRuleException $violation) {
            $this->addError("granted.{$role}", $violation->getMessage());

            return;
        }

        session()->flash('status', __('identity.role_permissions.restored', ['role' => $target->label()]));
        $this->loadGranted();
    }

    public function render(): View
    {
        $title = __('identity.role_permissions.title');
        $usersByRole = RoleModel::query()->where('guard_name', self::GUARD)->withCount('users')->pluck('users_count', 'name');

        return view('identity::livewire.role-permissions', [
            'roles' => Role::cases(),
            'permissions' => Permission::cases(),
            'usersByRole' => $usersByRole,
            'locked' => Role::AgencyOwner,
            'reserved' => Permission::RolesManage,
        ])->title($title)->layoutData(['heading' => $title]);
    }

    private function loadGranted(): void
    {
        $granted = [];
        foreach (RoleModel::query()->where('guard_name', self::GUARD)->with('permissions:id,name')->get() as $role) {
            /** @var list<string> $names */
            $names = $role->permissions->pluck('name')->sort()->values()->all();
            $granted[(string) $role->name] = $names;
        }

        $this->granted = $granted;
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
