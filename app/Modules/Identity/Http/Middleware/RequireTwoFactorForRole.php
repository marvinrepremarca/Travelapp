<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Los usuarios de roles que exigen 2FA ⚙ solo pueden usar la pantalla de seguridad
 * (y lo necesario para configurarla) hasta activarla.
 */
final class RequireTwoFactorForRole
{
    /**
     * Rutas permitidas mientras se configura el segundo factor.
     * `livewire.*` es seguro: un componente solo acepta actualizaciones de un snapshot firmado
     * que se obtiene al cargar su página, y las demás páginas están bloqueadas por este middleware.
     */
    private const ALLOWED_ROUTES = [
        'identity.security',
        'logout',
        'password.confirm',
        'password.confirmation',
        'two-factor.*',
        'user-password.update',
        'livewire.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->mustEnableTwoFactor() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('identity.security')->with('error', __('identity.security.required_notice'));
    }
}
