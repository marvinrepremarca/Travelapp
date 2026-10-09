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
    'customers.index',
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
    'catalog.index',
    'catalog.create',
    'quotes.index',
    'quotes.create',
    'bookings.index',
    'finance.revenue',
    'finance.payables',
    'finance.cash',
    'finance.profitability',
    'finance.bank-accounts',
    'finance.bank-accounts.create',
    'finance.reconciliation',
    'reports.advisor',
    'reports.management',
    'reports.finance',
    'portal.access',
    'operations.departures',
    'operations.resources',
    'operations.guides.create',
    'operations.vehicles.create',
    'compliance.index',
    'compliance.documents',
    'compliance.documents.create',
    'compliance.obligations.create',
    'compliance.requests',
    'compliance.requests.create',
    'portal.shop',
    'invoicing.index',
    'invoicing.create',
    'communications.inbox',
    'integrations.whatsapp-simulator',
    'search.flights',
    'search.hotels',
    'identity.security',
]);

it('renders the product and package sheets within the query budget', function (string $code): void {
    $this->seed(DemoSeeder::class);
    actingAs(userWithRole(Role::AgencyOwner));
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get(route('catalog.show', App\Modules\Catalog\Models\CatalogProduct::query()->where('code', $code)->sole()))->assertOk();

    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count(array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1)))->toBe(0);
})->with(['CTG-ROSARIO', 'CTG-3D']);

it('renders a sent quote within the query budget', function (): void {
    $this->seed(DemoSeeder::class);
    actingAs(userWithRole(Role::AgencyOwner));
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get(route('quotes.show', App\Modules\Quotes\Models\Quote::query()->where('title', 'Cartagena en familia')->sole()))->assertOk()->assertSee('Hotel Caribe Real');

    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count(array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1)))->toBe(0);
});

it('renders the customer quote link within the query budget', function (): void {
    $this->seed(DemoSeeder::class);
    $quote = App\Modules\Quotes\Models\Quote::query()->where('title', 'Cartagena en familia')->sole();
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get((string) app(App\Modules\Quotes\Services\QuoteLinks::class)->customerUrl($quote))->assertOk()->assertSee('Hotel Caribe Real');

    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count(array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1)))->toBe(0);
});

it('renders a booking within the query budget', function (): void {
    $this->seed(DemoSeeder::class);
    actingAs(userWithRole(Role::AgencyOwner));
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get(route('bookings.show', App\Modules\Bookings\Models\Booking::query()->sole()))->assertOk()->assertSee('HCR-20451');

    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count(array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1)))->toBe(0);
});

it('renders the booking payments within the query budget', function (): void {
    $this->seed(DemoSeeder::class);
    actingAs(userWithRole(Role::AgencyOwner));
    $queries = [];
    DB::listen(static function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql . json_encode($query->bindings);
    });

    get(route('payments.booking', App\Modules\Bookings\Models\Booking::query()->value('ulid')))->assertOk()->assertSee('TRX-DEMO-001');

    expect(count($queries))->toBeLessThanOrEqual(config()->integer('travel.performance.max_queries_per_screen'))
        ->and(count(array_filter(array_count_values($queries), static fn(int $times): bool => $times > 1)))->toBe(0);
});
