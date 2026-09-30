<?php

declare(strict_types=1);

namespace App\Modules\Audit\Listeners;

use App\Modules\Audit\Enums\SecurityEvent;
use App\Modules\Shared\Enums\AuditLogName;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\Events\TwoFactorAuthenticationDisabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationEnabled;
use Laravel\Fortify\Events\TwoFactorAuthenticationFailed;

/**
 * Deja en la bitácora de seguridad los eventos de autenticación y los replica en el canal de log `security`.
 * Nunca registra contraseñas ni códigos: solo el identificador usado y la IP.
 */
final readonly class RecordSecurityEvent
{
    public function __construct(private Request $request) {}

    public function handle(Login|Logout|Failed|Lockout|PasswordReset|TwoFactorAuthenticationEnabled|TwoFactorAuthenticationDisabled|TwoFactorAuthenticationFailed $event): void
    {
        [$type, $user, $identifier] = $this->describe($event);

        $properties = ['ip' => $this->request->ip(), 'identifier' => $identifier];

        $logger = activity(AuditLogName::Security->value)->event($type->value)->withProperties($properties);

        if ($user instanceof Model) {
            $logger->causedBy($user)->performedOn($user);
        }

        $logger->log($type->value);

        Log::channel(AuditLogName::Security->value)->info($type->value, [
            'user_id' => $user?->getAuthIdentifier(),
            ...$properties,
        ]);
    }

    /** @return array{SecurityEvent, Authenticatable|null, string|null} */
    private function describe(Login|Logout|Failed|Lockout|PasswordReset|TwoFactorAuthenticationEnabled|TwoFactorAuthenticationDisabled|TwoFactorAuthenticationFailed $event): array
    {
        return match (true) {
            $event instanceof Login => [SecurityEvent::LoggedIn, $this->user($event->user), null],
            $event instanceof Logout => [SecurityEvent::LoggedOut, $this->user($event->user), null],
            $event instanceof Failed => [SecurityEvent::LoginFailed, $this->user($event->user), $this->identifier($event->credentials)],
            $event instanceof Lockout => [SecurityEvent::LockedOut, null, $this->identifier($event->request->only('email'))],
            $event instanceof PasswordReset => [SecurityEvent::PasswordReset, $this->user($event->user), null],
            $event instanceof TwoFactorAuthenticationEnabled => [SecurityEvent::TwoFactorEnabled, $this->user($event->user), null],
            $event instanceof TwoFactorAuthenticationDisabled => [SecurityEvent::TwoFactorDisabled, $this->user($event->user), null],
            $event instanceof TwoFactorAuthenticationFailed => [SecurityEvent::TwoFactorFailed, $this->user($event->user), null],
        };
    }

    /** Los eventos de Fortify tipan un modelo de usuario genérico; aquí solo interesa el autenticable. */
    private function user(mixed $user): ?Authenticatable
    {
        return $user instanceof Authenticatable ? $user : null;
    }

    /** @param array<string, mixed> $credentials */
    private function identifier(array $credentials): ?string
    {
        $email = $credentials['email'] ?? null;

        return is_string($email) ? mb_strtolower($email) : null;
    }
}
