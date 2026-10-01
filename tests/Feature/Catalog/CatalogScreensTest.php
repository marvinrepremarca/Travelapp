<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Enums\DepartureStatus;
use App\Modules\Catalog\Livewire\CatalogIndex;
use App\Modules\Catalog\Livewire\ProductForm;
use App\Modules\Catalog\Livewire\ProductShow;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

function catalogManager(): App\Modules\Identity\Models\User
{
    return userWithRole(Role::ProductManager);
}

it('lets every internal user browse the catalog but only product managers edit it', function (): void {
    $product = CatalogProduct::factory()->create();

    get(route('catalog.index'))->assertRedirect(route('login'));
    actingAs(agent())->get(route('catalog.index'))->assertOk()->assertSee($product->name)->assertDontSee(__('catalog.create'));
    actingAs(agent())->get(route('catalog.show', $product))->assertOk()->assertDontSee(__('catalog.seasons.add'));
    actingAs(agent())->get(route('catalog.create'))->assertForbidden();
    actingAs(catalogManager())->get(route('catalog.create'))->assertOk();
});

it('answers 404 for unknown products', function (): void {
    actingAs(agent())->get(route('catalog.show', 'no-existe'))->assertNotFound();
});

it('filters the catalog by text and type and ignores unknown types', function (): void {
    CatalogProduct::factory()->create(['name' => 'Islas del Rosario', 'product_type' => ProductType::DayTrip]);
    CatalogProduct::factory()->create(['name' => 'Traslado aeropuerto', 'product_type' => ProductType::Transfer]);
    actingAs(agent());

    Livewire::test(CatalogIndex::class)->set('type', ProductType::Transfer->value)->assertSee('Traslado aeropuerto')->assertDontSee('Islas del Rosario');
    Livewire::test(CatalogIndex::class)->set('search', 'rosario')->assertSee('Islas del Rosario')->assertDontSee('Traslado aeropuerto');
    Livewire::test(CatalogIndex::class)->set('type', 'spaceship')->assertSee('Islas del Rosario')->assertSee('Traslado aeropuerto');
    Livewire::test(CatalogIndex::class)->set('search', 'nada')->assertSee(__('catalog.empty_title'));
});

it('creates and edits a product', function (): void {
    actingAs(catalogManager());

    Livewire::test(ProductForm::class)
        ->set('code', 'ctg-rosario')
        ->set('name', 'Islas del Rosario')
        ->set('product_type', ProductType::DayTrip->value)
        ->set('destination_country', 'co')
        ->set('destination_city', 'Cartagena')
        ->set('duration_minutes', '480')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $product = CatalogProduct::query()->sole();
    expect($product->code)->toBe('CTG-ROSARIO')
        ->and($product->destination_country)->toBe('CO')
        ->and($product->timezone)->toBe(config('travel.agency.timezone'))
        ->and($product->is_active)->toBeTrue();

    Livewire::test(ProductForm::class, ['product' => $product])
        ->assertSet('code', 'CTG-ROSARIO')
        ->set('name', 'Islas del Rosario Premium')
        ->call('save')
        ->assertHasNoErrors();
    expect($product->fresh()?->name)->toBe('Islas del Rosario Premium');
});

it('validates products', function (): void {
    CatalogProduct::factory()->create(['code' => 'DUP']);
    actingAs(catalogManager());

    Livewire::test(ProductForm::class)
        ->set('code', 'DUP')
        ->set('product_type', ProductType::Flight->value)
        ->set('timezone', 'Mars/Olympus')
        ->set('supplier_id', '999999')
        ->set('currency', '')
        ->call('save')
        ->assertHasErrors(['code', 'name', 'product_type', 'destination_country', 'destination_city', 'timezone', 'supplier_id', 'currency']);
});

it('adds seasons with rates and reports overlaps', function (): void {
    $product = CatalogProduct::factory()->create();
    actingAs(catalogManager());

    Livewire::test(ProductShow::class, ['product' => $product])
        ->set('season.name', 'Alta')
        ->set('season.starts_on', '2026-12-01')
        ->set('season.ends_on', '2027-01-15')
        ->set('season.adult', '150000')
        ->set('season.child', '90000')
        ->call('addSeason')
        ->assertHasNoErrors()
        ->assertSee('Alta')
        ->assertSee(__('catalog.seasons.no_rate'))
        ->set('season.name', 'Cruce')
        ->set('season.starts_on', '2027-01-10')
        ->set('season.ends_on', '2027-02-01')
        ->set('season.adult', '1')
        ->call('addSeason')
        ->assertHasErrors('season.starts_on');

    $rates = $product->seasons()->sole()->rates()->pluck('net_amount_minor', 'passenger_type')->all();
    expect($rates)->toBe(['adult' => 15000000, 'child' => 9000000]);
});

it('validates seasons', function (): void {
    actingAs(catalogManager());

    Livewire::test(ProductShow::class, ['product' => CatalogProduct::factory()->create()])
        ->set('season.starts_on', '2026-12-01')
        ->set('season.ends_on', '2026-11-01')
        ->set('season.child', '-5')
        ->call('addSeason')
        ->assertHasErrors(['season.name', 'season.ends_on', 'season.adult', 'season.child']);
});

it('schedules departures, rejects duplicates and closes sales', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 10:00'));
    $product = CatalogProduct::factory()->create();
    actingAs(catalogManager());

    $screen = Livewire::test(ProductShow::class, ['product' => $product])
        ->set('departure.service_date', '2026-10-20')
        ->set('departure.starts_at', '08:30')
        ->set('departure.capacity', '20')
        ->call('addDeparture')
        ->assertHasNoErrors()
        ->assertSee(__('catalog.departures.seats', ['available' => 20, 'capacity' => 20]))
        ->set('departure.service_date', '2026-10-20')
        ->set('departure.starts_at', '08:30')
        ->set('departure.capacity', '10')
        ->call('addDeparture')
        ->assertHasErrors('departure.starts_at');

    $departure = CatalogDeparture::query()->sole();
    $screen->call('toggleDeparture', $departure->ulid);
    expect($departure->fresh()?->status)->toBe(DepartureStatus::Closed);
});

it('validates departures', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 10:00'));
    actingAs(catalogManager());

    Livewire::test(ProductShow::class, ['product' => CatalogProduct::factory()->create()])
        ->set('departure.service_date', '2026-09-01')
        ->set('departure.starts_at', '8am')
        ->set('departure.capacity', (string) (config()->integer('travel.catalog.max_departure_capacity') + 1))
        ->call('addDeparture')
        ->assertHasErrors(['departure.service_date', 'departure.starts_at', 'departure.capacity']);
});

it('hides past departures', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 10:00'));
    $product = CatalogProduct::factory()->create();
    app(AddDepartureAction::class)->execute($product, CarbonImmutable::parse('2026-09-15'), '07:00', 9);
    actingAs(agent());

    Livewire::test(ProductShow::class, ['product' => $product])->assertSee(__('catalog.departures.empty'));
});

it('does not toggle departures of other products', function (): void {
    $other = app(AddDepartureAction::class)->execute(CatalogProduct::factory()->create(), CarbonImmutable::parse('2026-12-20'), '07:00', 9);
    actingAs(catalogManager());

    Livewire::test(ProductShow::class, ['product' => CatalogProduct::factory()->create()])
        ->call('toggleDeparture', $other->ulid)
        ->assertNotFound();
});

it('activates and deactivates products', function (): void {
    $product = CatalogProduct::factory()->create();
    actingAs(catalogManager());

    Livewire::test(ProductShow::class, ['product' => $product])->call('toggleActive')->assertSee(__('catalog.inactive'));

    expect($product->fresh()?->is_active)->toBeFalse();
});

it('forbids catalog changes to users without permission', function (string $method, array $arguments): void {
    $product = CatalogProduct::factory()->create();
    $owner = userWithRole(Role::AgencyOwner);
    actingAs($owner);
    $screen = Livewire::test(ProductShow::class, ['product' => $product]);
    $owner->syncRoles([Role::TravelAgent->value]);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $screen->call($method, ...$arguments)->assertForbidden();
})->with([
    'toggle product' => ['toggleActive', []],
    'add season' => ['addSeason', []],
    'add departure' => ['addDeparture', []],
    'toggle departure' => ['toggleDeparture', ['x']],
]);
