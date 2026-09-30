<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

use App\Modules\Shared\Enums\Tone;

/** Eventos de autenticación que quedan en la bitácora de seguridad. */
enum SecurityEvent: string
{
    case LoggedIn = 'logged_in';
    case LoggedOut = 'logged_out';
    case LoginFailed = 'login_failed';
    case LockedOut = 'locked_out';
    case PasswordReset = 'password_reset';
    case TwoFactorEnabled = 'two_factor_enabled';
    case TwoFactorDisabled = 'two_factor_disabled';
    case TwoFactorFailed = 'two_factor_failed';

    public function label(): string
    {
        return __("audit.security_events.{$this->value}");
    }

    public function tone(): Tone
    {
        return match ($this) {
            self::LoginFailed, self::LockedOut, self::TwoFactorFailed, self::TwoFactorDisabled => Tone::Danger,
            self::PasswordReset, self::TwoFactorEnabled => Tone::Warning,
            self::LoggedIn, self::LoggedOut => Tone::Neutral,
        };
    }
}
