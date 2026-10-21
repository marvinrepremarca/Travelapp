<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\Permission;
use App\Navigation\MainMenu;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Página de inicio. Quien administra la plataforma (permiso ⚙ "Ver las capacidades del sistema") ve qué capacidades
 * están encendidas; el resto ve su resumen: datos de su cuenta y accesos a lo que su rol puede hacer.
 */
final class HomeController
{
    public function __invoke(Request $request, MainMenu $menu, Capabilities $capabilities): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->can(Permission::CapabilitiesView->value)) {
            return view('dashboard', [
                'capabilities' => Capability::cases(),
                'capabilityStates' => $capabilities,
            ]);
        }

        $user->loadMissing(['branch:id,name', 'roles:id,name']);
        $role = Role::tryFrom((string) $user->getRoleNames()->first());

        return view('home', [
            'user' => $user,
            'roleLabel' => $role?->label(),
            'today' => CarbonImmutable::now(config()->string('travel.agency.timezone')),
            'stages' => $menu->for($user),
        ]);
    }
}
