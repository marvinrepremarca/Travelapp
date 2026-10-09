<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Reports\Livewire\AdvisorDashboard;
use App\Modules\Reports\Livewire\FinanceDashboard;
use App\Modules\Reports\Livewire\ManagementDashboard;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Navigation\MainMenu;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Matriz de independencia (ADR-0007): cada capacidad encendida sola sobre el núcleo (más las que necesita)
 * muestra todas sus pantallas y los tableros sin errores, y ninguna pantalla de otra capacidad.
 *
 * @param  list<Capability>  $enabled
 */
function switchCapabilities(array $enabled): void
{
    foreach (Capability::cases() as $capability) {
        config()->set("capabilities.enabled.{$capability->value}", in_array($capability, $enabled, true));
    }

    app()->forgetInstance(Capabilities::class);
}

dataset('single capabilities', array_combine(
    array_map(static fn(Capability $capability): string => $capability->value, Capability::cases()),
    array_map(static fn(Capability $capability): array => [[$capability, ...$capability->requires()]], Capability::cases()),
) + ['solo núcleo' => [[]]]);

it('serves every visible screen and dashboard with only these capabilities on', function (array $enabled): void {
    switchCapabilities($enabled);
    expect(app(Capabilities::class)->problems())->toBe([]);
    $owner = userWithRole(Role::AgencyOwner);
    // La pantalla de seguridad pide reconfirmar la contraseña: la sesión ya la trae confirmada.
    actingAs($owner)->withSession(['auth.password_confirmed_at' => time()]);

    $routes = collect(app(MainMenu::class)->for($owner))->pluck('steps')->flatten(1)->pluck('route')->push('dashboard');
    foreach ($routes as $route) {
        expect($this->get(route($route))->status())->toBe(200, "La pantalla {$route} no cargó");
    }

    Livewire::withoutLazyLoading()->test(AdvisorDashboard::class)->assertOk();
    Livewire::withoutLazyLoading()->test(ManagementDashboard::class)->assertOk();
    if (in_array(Capability::Accounting, $enabled, true)) {
        Livewire::withoutLazyLoading()->test(FinanceDashboard::class)->assertOk();
    }
})->with('single capabilities');

it('hides every screen of the capabilities that are off', function (array $enabled): void {
    switchCapabilities($enabled);
    $owner = userWithRole(Role::AgencyOwner);
    $capabilities = app(Capabilities::class);

    $visible = collect(app(MainMenu::class)->for($owner))->pluck('steps')->flatten(1)->pluck('route');

    foreach ($visible as $route) {
        expect($capabilities->allowsRoute($route))->toBeTrue();
    }
})->with('single capabilities');
