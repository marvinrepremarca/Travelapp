<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Auth\ResetUserPassword;
use App\Modules\Identity\Auth\UniformPasswordResetLinkResponse;
use App\Modules\Identity\Auth\UpdateUserPassword;
use App\Modules\Identity\Livewire\SecuritySettings;
use App\Modules\Identity\Livewire\UserForm;
use App\Modules\Identity\Livewire\UsersIndex;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Policies\UserPolicy;
use App\Modules\Identity\Services\RoleBasedBranchManagerDirectory;
use App\Modules\Organization\Contracts\BranchManagerDirectory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;
use Livewire\Livewire;

final class IdentityServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $singletons = [
        BranchManagerDirectory::class => RoleBasedBranchManagerDirectory::class,
    ];

    public function register(): void
    {
        $this->app->bind(
            FailedPasswordResetLinkRequestResponse::class,
            static fn(Application $app, array $parameters): UniformPasswordResetLinkResponse => new UniformPasswordResetLinkResponse($parameters['status']),
        );
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'identity');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        Livewire::component('identity.users-index', UsersIndex::class);
        Livewire::component('identity.user-form', UserForm::class);
        Livewire::component('identity.security-settings', SecuritySettings::class);

        Gate::policy(User::class, UserPolicy::class);

        $this->configureFortify();
        $this->configureRateLimiting();
    }

    private function configureFortify(): void
    {
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Usuarios inactivos no inician sesión; el mensaje es el mismo que para credenciales inválidas.
        Fortify::authenticateUsing(static function (Request $request): ?User {
            $user = User::query()->where('email', Str::lower($request->string(Fortify::username())->toString()))->first();

            return $user !== null && $user->isActive() && Hash::check($request->string('password')->toString(), $user->password)
                ? $user
                : null;
        });

        Fortify::loginView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.login'));
        Fortify::requestPasswordResetLinkView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.forgot-password'));
        Fortify::resetPasswordView(static fn(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.reset-password', ['request' => $request]));
        Fortify::twoFactorChallengeView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(static function (Request $request): \Illuminate\Contracts\View\View {
            $user = $request->user();

            return view('identity::auth.confirm-password', [
                'cancelUrl' => self::cancelUrl($request),
                'mustEnableTwoFactor' => $user instanceof User && $user->mustEnableTwoFactor(),
            ]);
        });
    }

    /**
     * "Cancelar" vuelve a la página desde la que llegó el usuario; si esa página es la misma
     * confirmación o la que la exige (evitaría un ciclo), vuelve al inicio.
     */
    private static function cancelUrl(Request $request): string
    {
        $previous = url()->previous();
        $blocked = [route('password.confirm'), (string) $request->session()->get('url.intended')];

        return in_array($previous, $blocked, true) || ! str_starts_with($previous, url('/'))
            ? route('dashboard')
            : $previous;
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('login', static function (Request $request): Limit {
            $throttleKey = Str::transliterate(Str::lower($request->string(Fortify::username())->toString()) . '|' . $request->ip());

            return Limit::perMinute(config()->integer('fortify.attempts_per_minute.login'))->by($throttleKey);
        });

        RateLimiter::for('two-factor', static fn(Request $request): Limit => Limit::perMinute(config()->integer('fortify.attempts_per_minute.two_factor'))
            ->by((string) $request->session()->get('login.id')));
    }
}
