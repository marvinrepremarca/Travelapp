<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\UpdateRolePermissionsAction;
use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\RolePermissionsViolation;
use App\Modules\Identity\Livewire\RolePermissions;
use App\Modules\Shared\Enums\Permission;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

it('lets only the agency owner manage role permissions', function (Role $role, int $status): void {
    actingAs(userWithRole($role))->get(route('identity.roles'))->assertStatus($status);
})->with([
    'gerente' => [Role::AgencyOwner, 200],
    'administrador del sistema' => [Role::SystemAdmin, 403],
    'finanzas' => [Role::Finance, 403],
    'asesor' => [Role::TravelAgent, 403],
]);

it('grants and revokes permissions of a role immediately and audits the change', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $agent = agent();
    actingAs($owner);
    expect($agent->fresh()?->can(Permission::MarginsView->value))->toBeFalse();

    Livewire::test(RolePermissions::class)
        ->set('granted.' . Role::TravelAgent->value, [Permission::MarginsView->value, Permission::CustomersReassign->value])
        ->call('save', Role::TravelAgent->value)
        ->assertHasNoErrors();

    expect($agent->fresh()?->can(Permission::MarginsView->value))->toBeTrue();
    $activity = Activity::query()->where('event', 'role_permissions_updated')->sole();
    expect($activity->causer_id)->toBe($owner->id)
        ->and($activity->properties['granted'])->toEqualCanonicalizing([Permission::MarginsView->value, Permission::CustomersReassign->value]);

    Livewire::test(RolePermissions::class)
        ->set('granted.' . Role::TravelAgent->value, [])
        ->call('save', Role::TravelAgent->value);
    expect($agent->fresh()?->can(Permission::MarginsView->value))->toBeFalse();
});

it('keeps the agency owner role untouchable and role management reserved', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $update = app(UpdateRolePermissionsAction::class);

    expect(fn() => $update->execute($owner, Role::AgencyOwner, []))->toThrow(RolePermissionsViolation::class)
        ->and(fn() => $update->execute($owner, Role::Finance, [Permission::RolesManage]))->toThrow(RolePermissionsViolation::class);

    actingAs($owner);
    Livewire::test(RolePermissions::class)
        ->set('granted.' . Role::BranchManager->value, [Permission::RolesManage->value])
        ->call('save', Role::BranchManager->value)
        ->assertHasErrors('granted.' . Role::BranchManager->value);
    expect($owner->fresh()?->can(Permission::RolesManage->value))->toBeTrue();
});

it('restores the default permissions of a role', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));
    $component = Livewire::test(RolePermissions::class)
        ->set('granted.' . Role::Finance->value, [])
        ->call('save', Role::Finance->value);

    $component->call('restoreDefaults', Role::Finance->value)->assertHasNoErrors();

    expect(financeUser()->can(Permission::FinanceAccess->value))->toBeTrue();
});

it('does not overwrite configured permissions when seeding again', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    app(UpdateRolePermissionsAction::class)->execute($owner, Role::TravelAgent, [Permission::MarginsView]);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(agent()->can(Permission::MarginsView->value))->toBeTrue()
        ->and(userWithRole(Role::SystemAdmin)->can(Permission::RolesManage->value))->toBeFalse()
        ->and($owner->fresh()?->can(Permission::ComplianceManage->value))->toBeTrue();
});
