<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Organization\Actions\UpdateSettingsAction;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Enums\MarkupKind;
use App\Modules\Pricing\Livewire\PriceSimulator;
use App\Modules\Pricing\Livewire\PricingRulesManager;
use App\Modules\Pricing\Models\FeeRule;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Pricing\Models\TaxRule;
use App\Modules\Shared\Enums\ProductType;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function productManager(): App\Modules\Identity\Models\User
{
    return userWithRole(Role::ProductManager);
}

it('protects the rules screen', function (): void {
    get(route('pricing.rules'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('pricing.rules'))->assertForbidden();
    actingAs(productManager())->get(route('pricing.rules'))->assertOk();
});

it('creates percentage and fixed markups, fees and taxes', function (): void {
    actingAs(productManager());

    Livewire::test(PricingRulesManager::class)
        ->set('markup.name', 'Hoteles 12.5')
        ->set('markup.kind', MarkupKind::Percentage->value)
        ->set('markup.value', '12.5')
        ->set('markup.product_type', ProductType::Hotel->value)
        ->set('markup.min_margin', '5')
        ->set('markup.valid_from', '2026-01-01')
        ->call('addMarkup')
        ->assertHasNoErrors()
        ->set('markup.name', 'Tours por pasajero')
        ->set('markup.kind', MarkupKind::FixedPerPassenger->value)
        ->set('markup.value', '15000')
        ->set('markup.currency', 'cop')
        ->set('markup.valid_from', '2026-01-01')
        ->call('addMarkup')
        ->assertHasNoErrors()
        ->set('tab', PricingRulesManager::TAB_FEES)
        ->set('fee.name', 'Gestión')
        ->set('fee.amount', '30000')
        ->set('fee.valid_from', '2026-01-01')
        ->call('addFee')
        ->assertHasNoErrors()
        ->set('tab', PricingRulesManager::TAB_TAXES)
        ->set('tax.name', 'IVA especial')
        ->set('tax.rate', '5')
        ->set('tax.exempt', [ProductType::Flight->value])
        ->set('tax.valid_from', '2026-01-01')
        ->call('addTax')
        ->assertHasNoErrors()
        ->assertSee('IVA especial');

    $percentage = MarkupRule::query()->where('name', 'Hoteles 12.5')->sole();
    $fixed = MarkupRule::query()->where('name', 'Tours por pasajero')->sole();

    expect($percentage->rate_basis_points)->toBe(1250)
        ->and($percentage->min_margin_basis_points)->toBe(500)
        ->and($fixed->amount_minor)->toBe(1500000)
        ->and($fixed->currency)->toBe('COP')
        ->and(FeeRule::query()->sole()->amount_minor)->toBe(3000000)
        ->and(TaxRule::query()->sole()->exempt_product_types)->toBe([ProductType::Flight->value]);
});

it('validates rules', function (): void {
    actingAs(productManager());

    Livewire::test(PricingRulesManager::class)
        ->set('markup.kind', MarkupKind::FixedPerBooking->value)
        ->set('markup.value', '-1')
        ->set('markup.currency', '')
        ->set('markup.valid_from', '2026-02-01')
        ->set('markup.valid_until', '2026-01-01')
        ->call('addMarkup')
        ->assertHasErrors(['markup.name', 'markup.value', 'markup.currency', 'markup.valid_until'])
        ->call('addFee')
        ->assertHasErrors(['fee.name', 'fee.amount', 'fee.valid_from'])
        ->set('tax.rate', '150')
        ->call('addTax')
        ->assertHasErrors(['tax.name', 'tax.rate']);
});

it('activates and deactivates rules and rejects unknown ones', function (): void {
    $rule = MarkupRule::factory()->create();
    actingAs(productManager());

    $screen = Livewire::test(PricingRulesManager::class)->call('toggle', PricingRulesManager::TAB_MARKUPS, $rule->ulid);
    expect($rule->fresh()?->is_active)->toBeFalse();

    Livewire::test(PricingRulesManager::class)->call('toggle', 'spaceships', $rule->ulid)->assertNotFound();
    Livewire::test(PricingRulesManager::class)->call('toggle', PricingRulesManager::TAB_FEES, 'no-existe')->assertNotFound();
});

it('forbids rule changes to users without permission', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner);
    $screen = Livewire::test(PricingRulesManager::class);
    $owner->syncRoles([Role::TravelAgent->value]);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $screen->set('markup.name', 'x')->call('addMarkup')->assertForbidden();
});

it('simulates a price with breakdown, hiding the margin from agents by default', function (): void {
    $this->seed(TaxReferenceSeeder::class);
    MarkupRule::factory()->percentage(1000)->create(['name' => 'General 10']);
    actingAs(agent());

    Livewire::test(PriceSimulator::class)
        ->set('net', '1000000')
        ->call('calculate')
        ->assertHasNoErrors()
        ->assertSee('General 10')
        ->assertSee('1.119.000')
        ->assertDontSee(__('pricing.simulator.margin', ['amount' => '$ 100.000']));

    actingAs(productManager());
    Livewire::test(PriceSimulator::class)->set('net', '1000000')->call('calculate')
        ->assertSee(__('pricing.simulator.margin', ['amount' => app(App\Modules\Shared\Money\MoneyPresenter::class)->format(Brick\Money\Money::of('100000', 'COP'))]));
});

it('shows the margin to agents when the agency allows it', function (): void {
    $owner = userWithRole(Role::AgencyOwner);
    $values = [];
    foreach (App\Modules\Organization\Enums\SettingKey::cases() as $key) {
        $values[$key->field()] = app(App\Modules\Organization\Services\SettingsStore::class)->get($key);
    }
    $values['visibility__hide_margins_from_agents'] = '0';
    app(UpdateSettingsAction::class)->execute($values);
    MarkupRule::factory()->percentage(1000)->create();
    actingAs(agent());

    Livewire::test(PriceSimulator::class)->set('net', '1000')->call('calculate')->assertSee(__('pricing.simulator.margin', ['amount' => '']), false);

    unset($owner);
});

it('validates the simulator and reports missing exchange rates', function (): void {
    actingAs(agent());

    Livewire::test(PriceSimulator::class)
        ->set('net', '0')
        ->set('passengers', '0')
        ->call('calculate')
        ->assertHasErrors(['net', 'passengers'])
        ->set('net', '100')
        ->set('passengers', '1')
        ->set('net_currency', 'EUR')
        ->call('calculate')
        ->assertHasErrors('net_currency')
        ->assertSee(__('pricing.simulator.empty'));
});
