<?php

declare(strict_types=1);

namespace App\Modules\Shared\Capabilities;

use App\Modules\Shared\Enums\Capability;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Una capacidad apagada no existe para el usuario: 404, igual que un recurso fuera de alcance. */
final readonly class EnsureCapabilityEnabled
{
    public function __construct(private Capabilities $capabilities) {}

    public function handle(Request $request, Closure $next, string ...$capabilities): Response
    {
        foreach ($capabilities as $value) {
            abort_unless($this->capabilities->enabled(Capability::from($value)), Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
