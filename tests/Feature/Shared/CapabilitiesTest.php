<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Integrations\Adapters\FakePayments\FakePaymentGateway;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Capabilities\CapabilityConfigurationInvalid;
use App\Modules\Shared\Enums\Capability;
use App\Navigation\MainMenu;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\call;

/** Rutas representativas de cada capacidad (ADR-0007). */
dataset('capability screens', [
    'comercial' => [Capability::Commercial, 'crm.leads.index'],
    'cotizaciones' => [Capability::Quoting, 'quotes.index'],
    'búsqueda' => [Capability::Quoting, 'search.flights'],
    'producto propio' => [Capability::OwnProduct, 'catalog.index'],
    'reservas' => [Capability::Bookings, 'bookings.index'],
    'operación' => [Capability::Operations, 'operations.departures'],
    'contabilidad' => [Capability::Accounting, 'finance.payables'],
    'tablero financiero' => [Capability::Accounting, 'reports.finance'],
    'facturación' => [Capability::Invoicing, 'invoicing.index'],
    'mensajería' => [Capability::Messaging, 'communications.inbox'],
    'cumplimiento' => [Capability::Compliance, 'compliance.index'],
]);

it('serves a screen while its capability is on and answers 404 once it is off', function (Capability $capability, string $route): void {
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner)->get(route($route))->assertOk();

    disableCapabilities($capability);

    actingAs($owner)->get(route($route))->assertNotFound();
})->with('capability screens');

it('removes the screens of a disabled capability from the menu', function (Capability $capability, string $route): void {
    disableCapabilities($capability);

    $routes = collect(app(MainMenu::class)->for(userWithRole(Role::AgencyOwner)))->pluck('steps')->flatten(1)->pluck('route');

    expect($routes)->not->toContain($route);
})->with('capability screens');

it('keeps quoting fully usable with accounting, invoicing and collections off', function (): void {
    disableCapabilities(Capability::Accounting, Capability::Invoicing, Capability::Collections);
    $agent = agent();

    actingAs($agent)->get(route('quotes.index'))->assertOk();
    actingAs($agent)->get(route('quotes.create'))->assertOk();
    actingAs($agent)->get(route('dashboard'))->assertOk()->assertDontSee(__('navigation.items.invoicing.label'));
});

it('keeps core customers available when the commercial capability is off', function (): void {
    disableCapabilities(Capability::Commercial);

    actingAs(agent())->get(route('crm.customers.index'))->assertOk();
});

it('keeps receiving gateway webhooks with collections off so no payment is lost', function (): void {
    disableCapabilities(Capability::Collections);
    $body = (string) json_encode(['event_id' => 'evt-off', 'reference' => 'FAKEPAY-NOEXISTE', 'outcome' => 'approved']);

    call('POST', route('payments.webhook', FakePaymentGateway::KEY), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_' . str_replace('-', '_', mb_strtoupper(FakePaymentGateway::SIGNATURE_HEADER)) => FakePaymentGateway::sign($body),
    ], $body)->assertStatus(202);
});

it('rejects a configuration where a capability lacks a required one', function (): void {
    disableCapabilities(Capability::Bookings);

    expect(app(Capabilities::class)->problems())->toHaveCount(1)
        ->and(fn() => app(Capabilities::class)->assertConsistent())->toThrow(CapabilityConfigurationInvalid::class);

    artisan('capabilities:status', ['--check' => true])->assertFailed();
});

it('reports a valid configuration to the deploy check', function (): void {
    disableCapabilities(Capability::Accounting);

    artisan('capabilities:status', ['--check' => true])
        ->expectsOutputToContain(__('capabilities.status.ok'))
        ->assertSuccessful();
});
