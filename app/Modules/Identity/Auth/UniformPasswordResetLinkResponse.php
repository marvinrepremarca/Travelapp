<?php

declare(strict_types=1);

namespace App\Modules\Identity\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;

/**
 * Responde igual exista o no el correo, para no permitir enumeración de usuarios (OWASP A07).
 * Los errores de throttling sí se informan, porque no revelan la existencia de la cuenta.
 */
final readonly class UniformPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(private string $status) {}

    /** @param Request $request */
    public function toResponse($request): RedirectResponse|JsonResponse
    {
        if ($this->status === Password::RESET_THROTTLED) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($this->status)]);
        }

        return $request->wantsJson()
            ? new JsonResponse(['message' => __(Password::RESET_LINK_SENT)])
            : back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
