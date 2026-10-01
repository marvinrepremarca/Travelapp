<?php

declare(strict_types=1);

use App\Modules\Identity\Database\Seeders\RolesAndPermissionsSeeder;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
    })
    ->in('Unit', 'Arch');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function (): void {
        Http::preventStrayRequests();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    })
    ->in('Feature');

/** Módulos del monolito: la lista es la fuente para los arch tests de límites. */
const MODULES = ['Shared', 'Organization', 'Identity', 'Audit', 'Workflow', 'Crm', 'Suppliers'];

/** Carpetas que forman la API pública de un módulo (skill modular-architecture). */
const MODULE_PUBLIC_API = ['Contracts', 'Data', 'Enums', 'Events', 'Models'];

/** Carpetas internas: solo el propio módulo puede usarlas. */
const MODULE_INTERNALS = ['Actions', 'Services', 'Http', 'Jobs', 'Listeners', 'Policies', 'Queries', 'Livewire', 'Sagas', 'Database'];

function userWithRole(Role $role): User
{
    $factory = User::factory()->withRole($role);

    return ($role->requiresTwoFactor() ? $factory->withTwoFactor() : $factory)->create();
}

function agent(): User
{
    return userWithRole(Role::TravelAgent);
}

function branchManager(): User
{
    return userWithRole(Role::BranchManager);
}

function financeUser(): User
{
    return userWithRole(Role::Finance);
}

/**
 * Namespaces `App\Modules\<Modulo>\<Capa>` que existen, para arch tests por capa.
 * Si ningún módulo tiene la capa todavía, devuelve un namespace centinela vacío.
 *
 * @return list<string>
 */
function moduleLayer(string $layer): array
{
    $namespaces = array_values(array_map(
        static fn(string $module): string => 'App\\Modules\\' . $module . '\\' . $layer,
        array_filter(MODULES, static fn(string $module): bool => is_dir(dirname(__DIR__) . "/app/Modules/{$module}/{$layer}")),
    ));

    return $namespaces === [] ? ['App\\Modules\\__none__'] : $namespaces;
}
