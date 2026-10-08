<?php

declare(strict_types=1);

use App\Modules\Identity\Http\Middleware\RequireTwoFactorForRole;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Capabilities\EnsureCapabilityEnabled;
use App\Modules\Shared\Routing\PathPrefix;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // Rutas web bajo la carpeta de publicación (APP_PATH_PREFIX); /up queda en la misma carpeta.
        using: static function (): void {
            Route::middleware('web')->group(static fn() => PathPrefix::load(base_path('routes/web.php')));
            Route::get(PathPrefix::path('up'), static fn() => response()->noContent())->name('up');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', RequireTwoFactorForRole::class);
        $middleware->alias([Capabilities::MIDDLEWARE => EnsureCapabilityEnabled::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
