<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;

/**
 * Recorrido real del navegador: un rol que exige 2FA y aún no lo activa entra, confirma su contraseña y llega a
 * Seguridad para activarlo. Antes el envío de la confirmación quedaba bloqueado y el flujo se repetía sin fin.
 */
it('walks a required role from login through password confirmation to the security screen', function (Role $role): void {
    $user = User::factory()->withRole($role)->create(['password' => 'Clave-Segura-2026']);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'Clave-Segura-2026'])->assertRedirect();
    $this->get(route('dashboard'))->assertRedirect(route('identity.security'));
    $this->get(route('identity.security'))->assertRedirect(route('password.confirm'));
    $this->get(route('password.confirm'))->assertOk();

    $this->post(route('password.confirm.store'), ['password' => 'Clave-Segura-2026'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('identity.security'));

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
    $this->get(route('identity.security'))->assertOk()->assertSee(__('identity.security.required_notice'));
    $this->get(route('dashboard'))->assertRedirect(route('identity.security'));
})->with([Role::SystemAdmin, Role::AgencyOwner, Role::Finance]);

it('rejects a wrong password in the confirmation without looping', function (): void {
    $admin = User::factory()->withRole(Role::SystemAdmin)->create(['password' => 'Clave-Segura-2026']);
    $this->post(route('login'), ['email' => $admin->email, 'password' => 'Clave-Segura-2026']);
    $this->get(route('password.confirm'));

    $this->post(route('password.confirm.store'), ['password' => 'otra-clave'])->assertSessionHasErrors('password');

    expect(session('auth.password_confirmed_at'))->toBeNull();
});
