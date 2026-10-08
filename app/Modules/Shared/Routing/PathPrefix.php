<?php

declare(strict_types=1);

namespace App\Modules\Shared\Routing;

use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use Illuminate\Support\Facades\Route;

/**
 * Carpeta bajo la que se publica la aplicación (p. ej. /travelapp), configurable con APP_PATH_PREFIX.
 * Vacío = la aplicación vive en la raíz del dominio.
 */
final class PathPrefix
{
    public static function value(): string
    {
        return trim(config()->string('app.path_prefix'), '/');
    }

    /** Ruta absoluta con el prefijo: path('livewire/update') → /travelapp/livewire/update. */
    public static function path(string $path = ''): string
    {
        return '/' . trim(self::value() . '/' . trim($path, '/'), '/');
    }

    /** Registra un archivo de rutas bajo el prefijo; si pertenece a capacidades activables, responde 404 cuando estén apagadas. */
    public static function load(string $routesFile, Capability ...$capabilities): void
    {
        $routes = Route::prefix(self::value());
        if ($capabilities !== []) {
            $routes->middleware(Capabilities::middleware(...$capabilities));
        }

        $routes->group($routesFile);
    }
}
