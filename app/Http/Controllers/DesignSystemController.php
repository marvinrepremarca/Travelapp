<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Muestra del sistema de diseño; solo existe (200) si la bandera ⚙ está activa. */
final class DesignSystemController
{
    public function __invoke(): View
    {
        abort_unless(config()->boolean('travel.ui.showcase_enabled'), 404);

        return view('design-system');
    }
}
