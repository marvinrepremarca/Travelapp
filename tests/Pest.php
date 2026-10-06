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
    ->in('Feature', 'Contract');

/** Módulos del monolito: la lista es la fuente para los arch tests de límites. */
const MODULES = ['Shared', 'Organization', 'Identity', 'Audit', 'Workflow', 'Crm', 'Suppliers', 'Pricing', 'Catalog', 'Quotes', 'Bookings', 'Documents', 'Search', 'Payments', 'Finance', 'Invoicing', 'Communications', 'Integrations'];

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

/** Expediente con un hotel cotizado para un adulto y un niño de 8 años, el 2026-11-10. */
/** Abre la caja de la sucursal del usuario (si no está abierta) para poder recibir abonos en efectivo. */
function openCashFor(\App\Modules\Identity\Models\User $user): void
{
    if ($user->branch_id !== null && app(\App\Modules\Finance\Services\CashDesk::class)->openSessionFor($user->branch_id) === null) {
        app(\App\Modules\Finance\Actions\OpenCashSessionAction::class)->execute($user, $user->branch_id, \Brick\Money\Money::zero('COP'), \Carbon\CarbonImmutable::now());
    }
}

function familyBooking(\App\Modules\Identity\Models\User $agent): \App\Modules\Bookings\Models\Booking
{
    openCashFor($agent);
    $customer = \App\Modules\Crm\Models\Customer::factory()->ownedBy($agent)->create();
    $quote = app(\App\Modules\Quotes\Actions\CreateQuoteAction::class)->execute($agent, $customer, 'Familia', 'COP', \App\Modules\Shared\Enums\SalesChannel::Branch);
    $option = $quote->options()->firstOrFail();
    app(\App\Modules\Quotes\Actions\AddItemAction::class)->execute($quote, $option, new \App\Modules\Quotes\Data\QuoteItemData(
        kind: \App\Modules\Quotes\Enums\QuoteItemKind::Manual,
        serviceDate: \Carbon\CarbonImmutable::parse('2026-11-10'),
        passengerAges: [40, 8],
        nights: 2,
        productType: \App\Modules\Shared\Enums\ProductType::Hotel,
        description: 'Hotel Caribe',
        manualNet: \Brick\Money\Money::of('500000', 'COP'),
    ));
    app(\App\Modules\Quotes\Actions\SendQuoteAction::class)->execute($quote, $agent, \Carbon\CarbonImmutable::now());
    app(\App\Modules\Quotes\Actions\AcceptQuoteAction::class)->execute($quote, $option->ulid, \App\Modules\Quotes\Enums\AcceptanceChannel::Agent, 'ok', \Carbon\CarbonImmutable::now());

    return app(\App\Modules\Bookings\Actions\CreateBookingFromQuoteAction::class)->execute($quote->ulid, \Carbon\CarbonImmutable::now());
}

/** Único servicio del expediente de familyBooking(). */
function hotelOf(\App\Modules\Bookings\Models\Booking $booking): \App\Modules\Bookings\Models\BookingItem
{
    return $booking->items()->sole();
}
