<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

it('redirects guests from the backoffice to the login', function (): void {
    get(route('dashboard'))->assertRedirect(route('login'));
});

it('renders the login screen in spanish', function (): void {
    get(route('login'))
        ->assertOk()
        ->assertSee(__('identity.auth.login_title'))
        ->assertSee(__('identity.fields.email'));
});

it('logs in with valid credentials', function (): void {
    $user = agent();

    post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(config('fortify.home'));

    assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function (): void {
    $user = agent();

    post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors(['email' => __('auth.failed')]);

    assertGuest();
});

it('validates required login fields', function (): void {
    post(route('login'), [])->assertSessionHasErrors(['email', 'password']);
});

it('throttles repeated failed logins', function (): void {
    $user = agent();
    $attempts = config()->integer('fortify.attempts_per_minute.login');

    foreach (range(1, $attempts) as $attempt) {
        post(route('login'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertTooManyRequests();
    assertGuest();
});

it('shows the dashboard to authenticated users', function (): void {
    actingAs(agent())->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__('shared.dashboard_empty_title'));
});

it('logs out', function (): void {
    actingAs(agent())->post(route('logout'))->assertRedirect('/');

    assertGuest();
});

it('does not expose public registration', function (): void {
    expect(Features::enabled(Features::registration()))->toBeFalse();
    get('/register')->assertNotFound();
});

it('sends a reset link without revealing whether the email exists', function (): void {
    Notification::fake();
    $user = agent();

    post(route('password.email'), ['email' => $user->email])->assertSessionHas('status', __('passwords.sent'));
    post(route('password.email'), ['email' => 'unknown@example.com'])->assertSessionHas('status', __('passwords.user'));

    Notification::assertSentTo($user, ResetPassword::class);
    expect(__('passwords.user'))->toBe(__('passwords.sent'));
});

it('resets the password with a valid token and enforces the password policy', function (): void {
    Notification::fake();
    $user = agent();
    post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
        get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))->assertOk();

        post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'NewSecurePass123',
            'password_confirmation' => 'NewSecurePass123',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect(auth()->attempt(['email' => $user->email, 'password' => 'NewSecurePass123']))->toBeTrue();
});

it('updates the password only with the current password', function (): void {
    $user = agent();

    actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'NewSecurePass123',
        'password_confirmation' => 'NewSecurePass123',
    ])->assertSessionHasErrorsIn('updatePassword', ['current_password' => __('identity.auth.current_password_mismatch')]);

    actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'NewSecurePass123',
        'password_confirmation' => 'NewSecurePass123',
    ])->assertSessionHasNoErrors();

    expect(auth()->guard('web')->getProvider()->validateCredentials($user->fresh(), ['password' => 'NewSecurePass123']))->toBeTrue();
});

it('requires the two factor challenge when enabled', function (): void {
    $user = agent();
    $user->forceFill([
        'two_factor_secret' => encrypt('SECRETSECRETSECR'),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('two-factor.login'));
    assertGuest();

    get(route('two-factor.login'))->assertOk()->assertSee(__('identity.auth.two_factor_title'));

    post(route('two-factor.login'), ['recovery_code' => 'recovery-code-1'])->assertRedirect(config('fortify.home'));
    assertAuthenticatedAs($user);
});

it('renders the password confirmation screen', function (): void {
    actingAs(agent())->get(route('password.confirm'))
        ->assertOk()
        ->assertSee(__('identity.auth.confirm_password_title'));
});

it('renders the forgot password screen', function (): void {
    get(route('password.request'))->assertOk()->assertSee(__('identity.auth.forgot_password_title'));
});

it('keeps the user model hidden fields out of serialization', function (): void {
    expect(agent()->toArray())->not->toHaveKeys(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes']);
});

it('relates the user to its branch', function (): void {
    $user = User::factory()->create();

    expect($user->branch)->not->toBeNull()
        ->and($user->viewerBranchId())->toBe($user->branch->id);
});

it('answers the reset link request as json without revealing the account', function (): void {
    postJson(route('password.email'), ['email' => 'unknown@example.com'])
        ->assertOk()
        ->assertJson(['message' => __('passwords.sent')]);
});

it('reports throttling of reset link requests', function (): void {
    Notification::fake();
    $user = agent();

    post(route('password.email'), ['email' => $user->email]);
    post(route('password.email'), ['email' => $user->email])
        ->assertSessionHasErrors(['email' => __('passwords.throttled')]);
});

it('builds users with an explicit scope and unverified email', function (): void {
    $user = User::factory()->withScope(VisibilityScope::All)->unverified()->create();

    expect($user->visibilityScope())->toBe(VisibilityScope::All)
        ->and($user->email_verified_at)->toBeNull();
});
