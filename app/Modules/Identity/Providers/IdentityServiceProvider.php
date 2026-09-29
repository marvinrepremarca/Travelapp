<?php

declare(strict_types=1);

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Auth\ResetUserPassword;
use App\Modules\Identity\Auth\UniformPasswordResetLinkResponse;
use App\Modules\Identity\Auth\UpdateUserPassword;
use App\Modules\Identity\Services\RoleBasedBranchManagerDirectory;
use App\Modules\Organization\Contracts\BranchManagerDirectory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Fortify;

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

        $this->configureFortify();
        $this->configureRateLimiting();
    }

    private function configureFortify(): void
    {
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.login'));
        Fortify::requestPasswordResetLinkView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.forgot-password'));
        Fortify::resetPasswordView(static fn(Request $request): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.reset-password', ['request' => $request]));
        Fortify::twoFactorChallengeView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.two-factor-challenge'));
        Fortify::confirmPasswordView(static fn(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('identity::auth.confirm-password'));
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
