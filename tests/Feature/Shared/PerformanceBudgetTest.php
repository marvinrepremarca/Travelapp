<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/*
 * Presupuesto de consultas por pantalla (regla performance.md): con los datos de demostración,
 * ninguna pantalla supera el máximo y ninguna consulta se repite (síntoma de N+1 o de lecturas sin memorizar).
 */
it('renders every backoffice screen within the query budget', function (string $routeName): void {
    $this->seed(DemoSeeder::class);
    actingAs(userWithRole(Role::AgencyOwner))->withSession(['auth.password_confirmed_at' => time()]);
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get(route($routeName))->assertOk();

    $duplicates = array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1);
    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count($duplicates))->toBeLessThanOrEqual(config()->integer('travel.performance.max_duplicate_queries_per_screen'), implode(PHP_EOL, array_keys($duplicates)));
})->with([
    'dashboard',
    'suppliers.index',
    'crm.customers.index',
    'crm.leads.index',
    'pricing.rates',
    'pricing.rules',
    'pricing.simulator',
    'identity.users.index',
    'organization.settings',
    'organization.branches.index',
    'organization.holidays',
    'workflow.tasks',
    'workflow.approvals',
    'audit.index',
    'organization.agency',
    'identity.security',
]);
