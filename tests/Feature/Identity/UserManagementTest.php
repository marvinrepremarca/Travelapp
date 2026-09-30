<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\InviteUserAction;
use App\Modules\Identity\Actions\SetUserActiveAction;
use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Exceptions\UserManagementViolation;
use App\Modules\Identity\Livewire\UserForm;
use App\Modules\Identity\Livewire\UsersIndex;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\UserInvitation;
use App\Modules\Identity\Services\AssignableRoles;
use App\Modules\Organization\Models\Branch;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function agencyOwner(): User
{
    return userWithRole(Role::AgencyOwner);
}

it('redirects guests to login', function (string $route): void {
    get(route($route))->assertRedirect(route('login'));
})->with(['identity.users.index', 'identity.users.create']);

it('forbids users without the users permissions', function (): void {
    actingAs(agent())->get(route('identity.users.index'))->assertForbidden();
    actingAs(agent())->get(route('identity.users.create'))->assertForbidden();
});

it('lets branch managers see only the users of their branch, read only', function (): void {
    $manager = branchManager();
    $colleague = User::factory()->for($manager->branch)->create(['name' => 'Colega de sucursal']);
    $outsider = User::factory()->create(['name' => 'Otra sucursal']);
    actingAs($manager);

    Livewire::test(UsersIndex::class)
        ->assertSee($colleague->name)
        ->assertDontSee($outsider->name)
        ->assertDontSee(__('identity.users.create'))
        ->assertViewHas('canManage', false);

    get(route('identity.users.create'))->assertForbidden();
});

it('answers not found when editing a user outside the scope', function (): void {
    $manager = branchManager();
    $manager->givePermissionTo(App\Modules\Shared\Enums\Permission::UsersManage->value);
    $outsider = User::factory()->create();

    actingAs($manager)->get(route('identity.users.edit', $outsider))->assertNotFound();
    actingAs($manager)->get(route('identity.users.edit', User::factory()->for($manager->branch)->create()))->assertOk();
});

it('lists, searches and filters users by role', function (): void {
    actingAs(agencyOwner());
    $agent = userWithRole(Role::TravelAgent);
    $finance = userWithRole(Role::Finance);

    Livewire::test(UsersIndex::class)
        ->assertSee($agent->name)
        ->assertSee($finance->name)
        ->set('role', Role::Finance->value)
        ->assertSee($finance->name)
        ->assertDontSee($agent->email)
        ->set('role', '')
        ->set('search', $agent->email)
        ->assertSee($agent->name)
        ->assertDontSee($finance->email)
        ->set('search', 'nadie-coincide')
        ->assertSee(__('identity.users.empty_title'));
});

it('paginates users with the configured size', function (): void {
    config(['travel.identity.users_per_page' => 2]);
    actingAs(agencyOwner());
    User::factory()->count(3)->create();

    Livewire::test(UsersIndex::class)
        ->assertViewHas('users', fn($users): bool => $users->count() === 2 && $users->total() === User::query()->count());
});

it('invites a user who defines their own password', function (): void {
    Notification::fake();
    $branch = Branch::factory()->create();
    actingAs(agencyOwner());

    Livewire::test(UserForm::class)
        ->set('name', 'Laura Gómez')
        ->set('email', 'Laura@Agencia.test')
        ->set('role', Role::TravelAgent->value)
        ->assertSet('visibility_scope', VisibilityScope::Own->value)
        ->set('branch_id', (string) $branch->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('identity.users.index'));

    $user = User::query()->where('email', 'laura@agencia.test')->firstOrFail();
    expect($user->hasRole(Role::TravelAgent->value))->toBeTrue()
        ->and($user->branch_id)->toBe($branch->id)
        ->and($user->is_active)->toBeTrue()
        ->and(Activity::query()->where('log_name', AuditLogName::Identity->value)->where('subject_id', $user->id)->exists())->toBeTrue();

    Notification::assertSentTo($user, UserInvitation::class, function (UserInvitation $invitation) use ($user): bool {
        $mail = $invitation->toMail($user);

        return str_contains((string) $mail->actionUrl, $invitation->token)
            && $invitation->via($user) === ['mail']
            && $mail->subject === __('identity.invitation.subject', ['app' => config('app.name')]);
    });
});

it('edits a user and records the change', function (): void {
    $user = userWithRole(Role::TravelAgent);
    actingAs(agencyOwner());

    Livewire::test(UserForm::class, ['user' => $user])
        ->assertSet('role', Role::TravelAgent->value)
        ->set('role', Role::Operations->value)
        ->set('name', 'Nombre Actualizado')
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Nombre Actualizado')
        ->and($user->hasRole(Role::Operations->value))->toBeTrue()
        ->and($user->hasRole(Role::TravelAgent->value))->toBeFalse()
        ->and($user->visibilityScope())->toBe(VisibilityScope::All);
});

it('validates the user form', function (string $field, string $value): void {
    User::factory()->create(['email' => 'existe@agencia.test']);
    actingAs(agencyOwner());

    Livewire::test(UserForm::class)
        ->set('name', 'Válido')
        ->set('email', 'nuevo@agencia.test')
        ->set('role', Role::TravelAgent->value)
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors($field);
})->with([
    'duplicated email' => ['email', 'existe@agencia.test'],
    'invalid email' => ['email', 'no-es-correo'],
    'unknown role' => ['role', 'superuser'],
    'missing name' => ['name', ''],
    'unknown scope' => ['visibility_scope', 'galaxy'],
    'inexistent branch' => ['branch_id', '999999'],
]);

it('requires a branch for own or branch scopes', function (): void {
    actingAs(agencyOwner());

    Livewire::test(UserForm::class)
        ->set('name', 'Sin sucursal')
        ->set('email', 'sin@agencia.test')
        ->set('role', Role::TravelAgent->value)
        ->set('branch_id', '')
        ->call('save')
        ->assertHasErrors(['branch_id' => __('identity.errors.branch_required')]);
});

it('prevents privilege escalation when assigning roles', function (): void {
    $admin = userWithRole(Role::SystemAdmin);

    expect(app(AssignableRoles::class)->canAssign($admin, Role::Finance))->toBeFalse()
        ->and(app(AssignableRoles::class)->canAssign($admin, Role::AgencyOwner))->toBeFalse()
        ->and(app(AssignableRoles::class)->canAssign($admin, Role::TravelAgent))->toBeTrue()
        ->and(app(AssignableRoles::class)->canAssign(agent(), Role::TravelAgent))->toBeFalse()
        ->and(app(AssignableRoles::class)->for(agencyOwner()))->toBe(Role::cases());

    actingAs($admin);
    Livewire::test(UserForm::class)
        ->assertViewHas('roles', fn(array $roles): bool => ! array_key_exists(Role::Finance->value, $roles))
        ->set('name', 'Escalada')
        ->set('email', 'escalada@agencia.test')
        ->set('role', Role::Finance->value)
        ->set('visibility_scope', VisibilityScope::All->value)
        ->set('branch_id', '')
        ->call('save')
        ->assertHasErrors(['role' => __('identity.errors.role_not_assignable', ['role' => Role::Finance->label()])]);

    expect(User::query()->where('email', 'escalada@agencia.test')->exists())->toBeFalse();
});

it('does not let users change their own role', function (): void {
    $owner = agencyOwner();

    expect(fn() => app(App\Modules\Identity\Actions\UpdateUserAction::class)->execute(
        $owner,
        new UserData($owner->name, $owner->email, Role::TravelAgent, $owner->branch_id, VisibilityScope::Own),
        $owner,
    ))->toThrow(UserManagementViolation::class, __('identity.errors.cannot_change_own_role'));
});

it('deactivates a user, closes their sessions and blocks their login', function (): void {
    $user = userWithRole(Role::TravelAgent);
    DB::table('sessions')->insert(['id' => 'session-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp]);
    actingAs(agencyOwner());

    Livewire::test(UsersIndex::class)->call('toggleActive', $user->ulid);

    expect($user->fresh()?->is_active)->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    auth()->logout();
    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);
    $this->assertGuest();

    actingAs(agencyOwner());
    Livewire::test(UsersIndex::class)->call('toggleActive', $user->ulid);
    expect($user->fresh()?->is_active)->toBeTrue();
});

it('does not let users deactivate themselves', function (): void {
    $owner = agencyOwner();
    actingAs($owner);

    Livewire::test(UsersIndex::class)
        ->call('toggleActive', $owner->ulid)
        ->assertHasErrors(['actions' => __('identity.errors.cannot_deactivate_self')])
        ->assertSee(__('identity.errors.cannot_deactivate_self'));

    expect($owner->fresh()?->is_active)->toBeTrue();
});

it('resends the invitation', function (): void {
    Notification::fake();
    $user = userWithRole(Role::TravelAgent);
    actingAs(agencyOwner());

    Livewire::test(UsersIndex::class)->call('resendInvitation', $user->ulid);

    Notification::assertSentTo($user, UserInvitation::class);
});

it('answers not found for actions on users outside the scope', function (): void {
    $manager = branchManager();
    $manager->givePermissionTo(App\Modules\Shared\Enums\Permission::UsersManage->value);
    $outsider = userWithRole(Role::TravelAgent);
    actingAs($manager);

    Livewire::test(UsersIndex::class)->call('toggleActive', $outsider->ulid)->assertNotFound();
    expect($outsider->fresh()?->is_active)->toBeTrue();
});

it('invites through the action and exposes stable error codes', function (): void {
    Notification::fake();

    $user = app(InviteUserAction::class)->execute(
        new UserData('Ana', 'ana@agencia.test', Role::Operations, null, VisibilityScope::All),
        agencyOwner(),
    );

    expect($user->branch_id)->toBeNull()
        ->and(UserManagementViolation::cannotDeactivateSelf()->errorCode())->toBe('cannot_deactivate_self')
        ->and(UserManagementViolation::branchRequired()->errorCode())->toBe('branch_required');
});

it('keeps self deactivation guarded in the action', function (): void {
    $owner = agencyOwner();

    app(SetUserActiveAction::class)->execute($owner, false, $owner);
})->throws(UserManagementViolation::class);

it('forbids editing a visible user without the manage permission', function (): void {
    $manager = branchManager();
    $colleague = User::factory()->for($manager->branch)->create();

    actingAs($manager)->get(route('identity.users.edit', $colleague))->assertForbidden();
});

it('shows only themselves to viewers with own scope', function (): void {
    $viewer = agent();
    $viewer->givePermissionTo(App\Modules\Shared\Enums\Permission::UsersView->value);
    $colleague = User::factory()->for($viewer->branch)->create(['name' => 'Colega']);
    actingAs($viewer);

    Livewire::test(UsersIndex::class)
        ->assertSee($viewer->name)
        ->assertDontSee($colleague->name);
});

it('answers not found from the policy for users outside the scope', function (): void {
    $manager = branchManager();

    $response = Illuminate\Support\Facades\Gate::forUser($manager)->inspect('update', User::factory()->create());

    expect($response->denied())->toBeTrue()
        ->and($response->status())->toBe(404);
});
