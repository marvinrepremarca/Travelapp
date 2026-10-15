<?php

declare(strict_types=1);

use App\Modules\Identity\Actions\CreateUserWithTemporaryPasswordAction;
use App\Modules\Identity\Data\UserData;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Livewire\ChangeTemporaryPassword;
use App\Modules\Identity\Livewire\UserForm;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Notifications\UserInvitation;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function issuedPasswordFrom(string $html): string
{
    preg_match('/name="issued-password"[^>]*value="([^"]+)"/', $html, $matches);

    return html_entity_decode($matches[1] ?? '');
}

it('creates a user with a generated temporary password and sends no email', function (): void {
    Notification::fake();
    actingAs(userWithRole(Role::AgencyOwner));

    $component = Livewire::test(UserForm::class)
        ->set('name', 'Laura Pérez')
        ->set('email', 'laura@agencia.test')
        ->set('role', Role::Finance->value)
        ->set('visibility_scope', VisibilityScope::All->value)
        ->set('withTemporaryPassword', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee(__('identity.users.temporary_password_once'));

    $user = User::query()->where('email', 'laura@agencia.test')->sole();
    $password = issuedPasswordFrom($component->html());

    expect($user->must_change_password)->toBeTrue()
        ->and(strlen($password))->toBeGreaterThanOrEqual(12)
        ->and(Hash::check($password, $user->password))->toBeTrue();
    Notification::assertNothingSent();
});

it('still invites by email when no temporary password is requested', function (): void {
    Notification::fake();
    actingAs(userWithRole(Role::AgencyOwner));

    Livewire::test(UserForm::class)
        ->assertSet('withTemporaryPassword', true)
        ->set('withTemporaryPassword', false)
        ->set('name', 'Mario Ruiz')
        ->set('email', 'mario@agencia.test')
        ->set('role', Role::Finance->value)
        ->set('visibility_scope', VisibilityScope::All->value)
        ->call('save')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'mario@agencia.test')->sole();
    Notification::assertSentTo($user, UserInvitation::class);
    expect($user->must_change_password)->toBeFalse();
});

it('sends users with a temporary password to change it before using the app', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);
    actingAs($user);

    get(route('dashboard'))->assertRedirect(route('identity.password.change'));
    get(route('identity.users.index'))->assertRedirect(route('identity.password.change'));
    get(route('identity.password.change'))->assertOk();
});

it('frees the user once they define their own password', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);
    actingAs($user);

    Livewire::test(ChangeTemporaryPassword::class)
        ->set('password', 'Nueva-Clave2026')
        ->set('password_confirmation', 'Nueva-Clave2026')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse()
        ->and(Hash::check('Nueva-Clave2026', $user->password))->toBeTrue();
    get(route('dashboard'))->assertOk();
});

it('rejects reusing the temporary password', function (): void {
    $actor = userWithRole(Role::AgencyOwner);
    $issued = app(CreateUserWithTemporaryPasswordAction::class)->execute(
        new UserData('Pedro Gil', 'pedro@agencia.test', Role::Finance, null, VisibilityScope::All),
        $actor,
    );
    actingAs($issued['user']);

    Livewire::test(ChangeTemporaryPassword::class)
        ->set('password', $issued['password'])
        ->set('password_confirmation', $issued['password'])
        ->call('save')
        ->assertHasErrors(['password' => __('identity.password_change.must_differ')]);

    expect($issued['user']->fresh()?->must_change_password)->toBeTrue();
});
