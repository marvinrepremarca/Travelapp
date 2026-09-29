<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Identity\Enums\Permission;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\VisibilityScope;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

it('seeds every base role and permission', function (): void {
    expect(RoleModel::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(Role::cases())->map->value->sort()->values()->all())
        ->and(PermissionModel::query()->count())->toBe(count(Permission::cases()));
});

it('is idempotent', function (): void {
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(RoleModel::query()->count())->toBe(count(Role::cases()))
        ->and(PermissionModel::query()->count())->toBe(count(Permission::cases()));
});

it('grants each role exactly its permissions', function (Role $role): void {
    $user = userWithRole($role);

    foreach (Permission::cases() as $permission) {
        expect($user->can($permission->value))
            ->toBe(in_array($permission, $role->permissions(), true), "{$role->value} → {$permission->value}");
    }
})->with(Role::cases());

it('assigns the default scope of the role', function (Role $role, VisibilityScope $scope): void {
    expect(userWithRole($role)->visibilityScope())->toBe($scope);
})->with([
    [Role::AgencyOwner, VisibilityScope::All],
    [Role::BranchManager, VisibilityScope::Branch],
    [Role::TravelAgent, VisibilityScope::Own],
]);

it('reads the travel agent scope from configuration', function (): void {
    config(['travel.visibility.travel_agent_scope' => VisibilityScope::Branch->value]);

    expect(Role::TravelAgent->defaultScope())->toBe(VisibilityScope::Branch);
});

it('requires two factor only for configured roles', function (Role $role, bool $required): void {
    expect($role->requiresTwoFactor())->toBe($required);
})->with([
    [Role::AgencyOwner, true],
    [Role::Finance, true],
    [Role::TravelAgent, false],
]);

it('exposes translated labels', function (): void {
    expect(Role::TravelAgent->label())->toBe(__('identity.roles.travel_agent'))
        ->and(Permission::AuditView->label())->toBe(__('identity.permissions.audit.view'))
        ->and(VisibilityScope::Own->label())->toBe(__('shared.visibility_scope.own'));
});
