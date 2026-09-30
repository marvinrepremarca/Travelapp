<?php

declare(strict_types=1);

namespace App\Modules\Audit\Providers;

use App\Modules\Audit\Contracts\SensitiveDataAccessRecorder;
use App\Modules\Audit\Listeners\RecordSecurityEvent;
use App\Modules\Audit\Livewire\AuditLogIndex;
use App\Modules\Audit\Services\DatabaseSensitiveDataAccessRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;
use Livewire\Livewire;

final class AuditServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        SensitiveDataAccessRecorder::class => DatabaseSensitiveDataAccessRecorder::class,
    ];

    /** Eventos de autenticación que se auditan. */
    private const SECURITY_EVENTS = [
        Login::class,
        Logout::class,
        Failed::class,
        Lockout::class,
        PasswordReset::class,
        TwoFactorAuthenticationEnabled::class,
        TwoFactorAuthenticationDisabled::class,
        TwoFactorAuthenticationFailed::class,
    ];

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'audit');

        Livewire::component('audit.audit-log-index', AuditLogIndex::class);

        foreach (self::SECURITY_EVENTS as $event) {
            Event::listen($event, RecordSecurityEvent::class);
        }
    }
}
