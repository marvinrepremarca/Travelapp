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

    Livewire::test(RolePermissions::class, ['role' => Role::TravelAgent->value])
        ->set('granted', [Permission::MarginsView->value, Permission::CustomersReassign->value])
        ->call('save')
        ->assertHasNoErrors();

    expect($agent->fresh()?->can(Permission::MarginsView->value))->toBeTrue();
    $activity = Activity::query()->where('event', 'role_permissions_updated')->sole();
    expect($activity->causer_id)->toBe($owner->id)
        ->and($activity->properties['granted'])->toEqualCanonicalizing([Permission::MarginsView->value, Permission::CustomersReassign->value]);

    Livewire::test(RolePermissions::class, ['role' => Role::TravelAgent->value])
        ->set('granted', [])
        ->call('save');
    expect($agent->fresh()?->can(Permission::MarginsView->value))->toBeFalse();
});

it('keeps the agency owner role untouchable and role management reserved', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $update = app(UpdateRolePermissionsAction::class);

    expect(fn() => $update->execute($owner, Role::AgencyOwner, []))->toThrow(RolePermissionsViolation::class)
        ->and(fn() => $update->execute($owner, Role::Finance, [Permission::RolesManage]))->toThrow(RolePermissionsViolation::class);

    actingAs($owner);
    Livewire::test(RolePermissions::class, ['role' => Role::BranchManager->value])
        ->set('granted', [Permission::RolesManage->value])
        ->call('save')
        ->assertHasErrors('granted');
    expect($owner->fresh()?->can(Permission::RolesManage->value))->toBeTrue();
});

it('restores the default permissions of a role', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));
    $component = Livewire::test(RolePermissions::class, ['role' => Role::Finance->value])
        ->set('granted', [])
        ->call('save');

    $component->call('restoreDefaults')->assertHasNoErrors();

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

it('lists every role and opens each one on its own page', function (): void {
    actingAs(userWithRole(Role::AgencyOwner));

    $response = $this->get(route('identity.roles'))->assertOk();
    foreach (Role::cases() as $role) {
        $response->assertSee(route('identity.roles.edit', $role->value), false);
    }

    $this->get(route('identity.roles.edit', Role::Finance->value))->assertOk()->assertSee(Role::Finance->label());
    $this->get(route('identity.roles.edit', 'unknown'))->assertNotFound();
});

it('hides and blocks the business areas a role has no access to', function (): void {
    $admin = userWithRole(Role::SystemAdmin);
    actingAs($admin);

    $this->get(route('suppliers.index'))->assertForbidden();
    $this->get(route('customers.index'))->assertForbidden();
    $this->get(route('workflow.tasks'))->assertForbidden();
    $this->get(route('identity.users.index'))->assertOk()
        ->assertDontSee(route('suppliers.index'), false)
        ->assertDontSee(route('crm.leads.index'), false);

    app(UpdateRolePermissionsAction::class)->execute(userWithRole(Role::AgencyOwner), Role::SystemAdmin, [Permission::UsersView, Permission::SuppliersView]);

    $this->get(route('suppliers.index'))->assertOk();
});
