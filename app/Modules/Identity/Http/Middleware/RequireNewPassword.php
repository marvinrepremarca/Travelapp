<?php

declare(strict_types=1);

namespace App\Modules\Identity\Http\Middleware;

use App\Modules\Identity\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Quien entra con una contraseña temporal solo puede definir la suya antes de usar el sistema. */
final class RequireNewPassword
{
    /**
     * `livewire.*` es seguro: un componente solo acepta actualizaciones de un snapshot firmado
     * que se obtiene al cargar su página, y las demás páginas están bloqueadas por este middleware.
     */
    private const ALLOWED_ROUTES = [
        'identity.password.change',
        'logout',
        'livewire.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->mustChangePassword() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('identity.password.change');
    }
}
