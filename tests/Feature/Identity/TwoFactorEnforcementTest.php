<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Livewire\SecuritySettings;
use App\Modules\Identity\Models\User;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

function ownerWithoutTwoFactor(): User
{
    return User::factory()->withRole(Role::AgencyOwner)->create();
}

it('sends users whose role requires two factor to the security screen', function (): void {
    actingAs(ownerWithoutTwoFactor())
        ->get(route('organization.agency'))
        ->assertRedirect(route('identity.security'))
        ->assertSessionHas('error', __('identity.security.required_notice'));
});

it('lets them reach the security screen after confirming the password', function (): void {
    actingAs(ownerWithoutTwoFactor())
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('identity.security'))
        ->assertOk()
        ->assertSee(__('identity.security.required_notice'));
});

it('asks for the password before showing the security screen', function (): void {
    actingAs(agent())->get(route('identity.security'))->assertRedirect(route('password.confirm'));
});

it('does not force two factor on roles that do not require it', function (): void {
    actingAs(agent())->get(route('dashboard'))->assertOk();
});

it('enables, confirms and uses two factor from the security screen', function (): void {
    $user = ownerWithoutTwoFactor();
    actingAs($user);
    $provider = Mockery::mock(TwoFactorAuthenticationProvider::class);
    $provider->shouldReceive('generateSecretKey')->andReturn('SECRETSECRETSECR');
    $provider->shouldReceive('qrCodeUrl')->andReturn('otpauth://totp/test');
    $provider->shouldReceive('verify')->andReturnUsing(fn(string $secret, string $code): bool => $code === '123456');
    app()->instance(TwoFactorAuthenticationProvider::class, $provider);

    $component = Livewire::test(SecuritySettings::class)
        ->call('enable')
        ->assertSee(__('identity.security.scan_help'))
        ->assertSee('data:image/svg+xml;base64,', false)
        ->set('code', '000000')
        ->call('confirm')
        ->assertHasErrors('code');

    $component->set('code', '123456')->call('confirm')->assertHasNoErrors()
        ->assertSee(__('identity.security.recovery_codes_title'));

    expect($user->fresh()?->hasTwoFactorEnabled())->toBeTrue();
    actingAs($user->fresh())->get(route('organization.agency'))->assertOk();
});

it('does not let required roles disable two factor', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner);

    Livewire::test(SecuritySettings::class)
        ->call('disable')
        ->assertHasErrors(['two_factor' => __('identity.security.required_cannot_disable')]);

    expect($owner->fresh()?->hasTwoFactorEnabled())->toBeTrue();
});

it('lets optional users disable two factor and regenerate codes', function (): void {
    $agent = User::factory()->withRole(Role::TravelAgent)->withTwoFactor()->create();
    actingAs($agent);
    $before = $agent->two_factor_recovery_codes;

    Livewire::test(SecuritySettings::class)
        ->call('regenerateRecoveryCodes')
        ->assertSee(__('identity.security.recovery_codes_title'))
        ->call('disable');

    $agent->refresh();
    expect($agent->hasTwoFactorEnabled())->toBeFalse()
        ->and($agent->two_factor_recovery_codes)->not->toBe($before);
});

it('shows the security and users entries in the navigation', function (): void {
    actingAs(userWithRole(Role::AgencyOwner))->get(route('dashboard'))
        ->assertSee(route('identity.users.index'))
        ->assertSee(route('identity.security'));
});

it('lets the user cancel the password confirmation and go back', function (): void {
    $agent = agent();

    actingAs($agent)
        ->from(route('workflow.tasks'))
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('shared.cancel'))
        ->assertSee('href="' . route('workflow.tasks') . '"', false);
});

it('sends cancel to the dashboard when going back would loop', function (): void {
    actingAs(agent())
        ->withSession(['url.intended' => route('identity.security')])
        ->from(route('identity.security'))
        ->get(route('password.confirm'))
        ->assertSee('href="' . route('dashboard') . '"', false);
});

it('offers logout instead of cancel while two factor is pending', function (): void {
    actingAs(ownerWithoutTwoFactor())
        ->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('identity.auth.logout'))
        ->assertSee('action="' . route('logout') . '"', false)
        ->assertDontSee(__('shared.cancel'));
});
