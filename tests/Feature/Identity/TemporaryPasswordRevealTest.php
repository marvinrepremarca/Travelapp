<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\CreateUserWithTemporaryPasswordAction;
use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Livewire\ChangeTemporaryPassword;
use App\Modules\Identity\Livewire\UsersIndex;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/** @return array{user: App\Modules\Identity\Models\User, password: string} */
function issuedUserInBranchOf(App\Modules\Identity\Models\User $manager): array
{
    return app(CreateUserWithTemporaryPasswordAction::class)->execute(
        new UserData('Pedro Gil', 'pedro@agencia.test', Role::TravelAgent, $manager->branch_id, VisibilityScope::Branch),
        userWithRole(Role::AgencyOwner),
    );
}

it('stores the temporary password encrypted and hides it from serialization', function (): void {
    $issued = issuedUserInBranchOf(userWithRole(Role::BranchManager));

    $raw = DB::table('users')->where('id', $issued['user']->id)->value('temporary_password');
    expect($raw)->not->toBe($issued['password'])
        ->and($issued['user']->fresh()?->temporary_password)->toBe($issued['password'])
        ->and($issued['user']->fresh()?->toArray())->not->toHaveKey('temporary_password');
});

it('lets the branch manager reveal the temporary password and records the access', function (): void {
    $manager = userWithRole(Role::BranchManager);
    $issued = issuedUserInBranchOf($manager);
    actingAs($manager);

    Livewire::test(UsersIndex::class)
        ->call('revealTemporaryPassword', $issued['user']->ulid)
        ->assertHasNoErrors()
        ->assertSee($issued['password'])
        ->assertSee(__('identity.users.temporary_password_reveal_help'));

    expect(DB::table('activity_log')->where('event', 'temporary_password_revealed')->where('subject_id', $issued['user']->id)->exists())->toBeTrue();
});

it('grants the temporary password view only to managers and the owner', function (): void {
    expect(Role::BranchManager->permissions())->toContain(Permission::TemporaryPasswordsView)
        ->and(Role::TravelAgent->permissions())->not->toContain(Permission::TemporaryPasswordsView)
        ->and(Role::Finance->permissions())->not->toContain(Permission::TemporaryPasswordsView);
});

it('stops revealing the temporary password once the user defines their own', function (): void {
    $manager = userWithRole(Role::BranchManager);
    $issued = issuedUserInBranchOf($manager);
    actingAs($issued['user']);

    Livewire::test(ChangeTemporaryPassword::class)
        ->set('password', 'Nueva-Clave2026')
        ->set('password_confirmation', 'Nueva-Clave2026')
        ->call('save')
        ->assertHasNoErrors();

    $issued['user']->refresh();
    expect($issued['user']->temporary_password)->toBeNull()
        ->and(Hash::check('Nueva-Clave2026', $issued['user']->password))->toBeTrue();

    actingAs($manager);
    Livewire::test(UsersIndex::class)
        ->call('revealTemporaryPassword', $issued['user']->ulid)
        ->assertHasErrors('actions');
});
