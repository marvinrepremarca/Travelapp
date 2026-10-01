<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddPackageComponentAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Actions\SaveProductAction;
use App\Modules\Catalog\Contracts\CatalogRates;
use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Exceptions\CatalogRuleViolation;
use App\Modules\Catalog\Livewire\ProductShow;
use App\Modules\Catalog\Models\CatalogPackageComponent;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/** Paquete de 2 días: city tour el día 0 y pasadía el día 1; la pasadía cambia de temporada el 2026-12-01. */
function cartagenaPackage(): CatalogProduct
{
    $seasons = app(AddSeasonAction::class);
    $city = CatalogProduct::factory()->create(['name' => 'City tour']);
    $seasons->execute($city, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 9000000, 'child' => 6000000]);
    $rosario = CatalogProduct::factory()->create(['name' => 'Rosario', 'product_type' => ProductType::DayTrip]);
    $seasons->execute($rosario, 'Media', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-11-30'), ['adult' => 18000000, 'child' => 12000000]);
    $seasons->execute($rosario, 'Alta', CarbonImmutable::parse('2026-12-01'), CarbonImmutable::parse('2027-01-31'), ['adult' => 24000000, 'child' => 16000000]);

    $package = CatalogProduct::factory()->create(['name' => 'Cartagena 2 días', 'product_type' => ProductType::Package]);
    $add = app(AddPackageComponentAction::class);
    $add->execute($package, $city, 0);
    $add->execute($package, $rosario, 1);

    return $package;
}

it('prices each component on its own itinerary day and season', function (string $start, array $ages, string $total, string $seasons): void {
    $quote = app(CatalogRates::class)->netPriceFor(cartagenaPackage()->ulid, CarbonImmutable::parse($start), $ages);

    expect((string) $quote->total->getAmount())->toBe($total)
        ->and($quote->seasonName)->toBe($seasons);
})->with([
    'one adult in mid season' => ['2026-10-10', [30], '270000.00', 'Todo el año + Media'],
    'adult and child' => ['2026-10-10', [30, 7], '450000.00', 'Todo el año + Media'],
    'second day falls in high season' => ['2026-11-30', [30], '330000.00', 'Todo el año + Alta'],
]);

it('adds the unit cost of every component per passenger type', function (): void {
    $quote = app(CatalogRates::class)->netPriceFor(cartagenaPackage()->ulid, CarbonImmutable::parse('2026-10-10'), [30, 40, 7]);

    expect($quote->lines['adult']['count'])->toBe(2)
        ->and((string) $quote->lines['adult']['unit']->getAmount())->toBe('270000.00')
        ->and((string) $quote->lines['child']['unit']->getAmount())->toBe('180000.00');
});

it('refuses to price empty, inactive or partially seasoned packages', function (Closure $package, string $date): void {
    app(CatalogRates::class)->netPriceFor($package()->ulid, CarbonImmutable::parse($date), [30]);
})->throws(CatalogRuleViolation::class)->with([
    'empty package' => [fn() => CatalogProduct::factory()->create(['product_type' => ProductType::Package]), '2026-10-10'],
    'inactive package' => [fn() => tap(cartagenaPackage(), static function (CatalogProduct $package): void {
        $package->is_active = false;
        $package->save();
    }), '2026-10-10'],
    'component without season' => [cartagenaPackage(...), '2027-01-31'],
]);

it('computes the from price as the cheapest current or future adult rate', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01'));
    $package = cartagenaPackage();
    $rates = app(CatalogRates::class);
    $rosario = CatalogProduct::query()->where('name', 'Rosario')->sole();

    expect((string) $rates->fromPriceFor($package->ulid, CarbonImmutable::today())?->getAmount())->toBe('270000.00')
        ->and((string) $rates->fromPriceFor($rosario->ulid, CarbonImmutable::today())?->getAmount())->toBe('180000.00')
        ->and((string) $rates->fromPriceFor($rosario->ulid, CarbonImmutable::parse('2026-12-15'))?->getAmount())->toBe('240000.00')
        ->and($rates->fromPriceFor($rosario->ulid, CarbonImmutable::parse('2027-02-01')))->toBeNull()
        ->and($rates->fromPriceFor($package->ulid, CarbonImmutable::parse('2027-02-01')))->toBeNull()
        ->and($rates->fromPriceFor(CatalogProduct::factory()->create(['product_type' => ProductType::Package])->ulid, CarbonImmutable::today()))->toBeNull();
});

it('enforces package composition rules', function (Closure $scenario, string $message): void {
    expect($scenario)->toThrow(CatalogRuleViolation::class, __($message, ['currency' => 'COP']));
})->with([
    'components only in packages' => [fn() => app(AddPackageComponentAction::class)->execute(CatalogProduct::factory()->create(), CatalogProduct::factory()->create(), 0), 'catalog.errors.not_a_package'],
    'no nested packages' => [fn() => app(AddPackageComponentAction::class)->execute(cartagenaPackage(), CatalogProduct::factory()->create(['product_type' => ProductType::Package]), 0), 'catalog.errors.nested_package'],
    'same currency' => [fn() => app(AddPackageComponentAction::class)->execute(cartagenaPackage(), CatalogProduct::factory()->create(['currency' => 'USD']), 0), 'catalog.errors.component_currency_mismatch'],
    'no duplicates on the same day' => [fn() => app(AddPackageComponentAction::class)->execute(cartagenaPackage(), CatalogProduct::query()->where('name', 'City tour')->sole(), 0), 'catalog.errors.duplicated_component'],
]);

it('locks type and currency of products linked to packages', function (string $productName, array $changes): void {
    cartagenaPackage();
    $product = CatalogProduct::query()->where('name', $productName)->sole();
    $data = new ProductData(
        code: $product->code,
        name: $product->name,
        productType: $changes['type'] ?? $product->product_type,
        destinationCountry: $product->destination_country,
        destinationCity: $product->destination_city,
        timezone: $product->timezone,
        currency: $changes['currency'] ?? $product->currency,
    );

    expect(fn() => app(SaveProductAction::class)->execute($data, $product))
        ->toThrow(CatalogRuleViolation::class, __('catalog.errors.product_type_locked'));
})->with([
    'package becomes a tour' => ['Cartagena 2 días', ['type' => ProductType::Tour]],
    'component becomes a package' => ['City tour', ['type' => ProductType::Package]],
    'component changes currency' => ['Rosario', ['currency' => 'USD']],
]);

it('lets product managers compose a package from its sheet', function (): void {
    $package = cartagenaPackage();
    $transfer = CatalogProduct::factory()->create(['name' => 'Traslado salida', 'product_type' => ProductType::Transfer]);
    CatalogProduct::factory()->create(['name' => 'Tour en dólares', 'currency' => 'USD']);
    actingAs(userWithRole(Role::ProductManager));

    $screen = Livewire::test(ProductShow::class, ['product' => $package])
        ->assertSee(__('catalog.components.day', ['day' => 2]))
        ->assertSee(__('catalog.from_price_value', ['amount' => app(App\Modules\Shared\Money\MoneyPresenter::class)->format(Brick\Money\Money::of('270000', 'COP'))]))
        ->assertDontSee('Tour en dólares')
        ->set('component.product', $transfer->ulid)
        ->set('component.day_offset', '1')
        ->call('addComponent')
        ->assertHasNoErrors()
        ->assertSee('Traslado salida')
        ->set('component.product', $transfer->ulid)
        ->set('component.day_offset', '1')
        ->call('addComponent')
        ->assertHasErrors('component.product');

    $added = CatalogPackageComponent::query()->where('component_id', $transfer->id)->sole();
    $screen->call('removeComponent', $added->ulid);
    expect($package->components()->count())->toBe(2);
});

it('validates package components and hides other products components', function (): void {
    $package = cartagenaPackage();
    $foreign = CatalogPackageComponent::query()->where('package_id', $package->id)->firstOrFail();
    actingAs(userWithRole(Role::ProductManager));

    Livewire::test(ProductShow::class, ['product' => CatalogProduct::factory()->create(['product_type' => ProductType::Package])])
        ->set('component.product', 'no-existe')
        ->set('component.day_offset', (string) (config()->integer('travel.catalog.max_package_days') + 1))
        ->call('addComponent')
        ->assertHasErrors(['component.product', 'component.day_offset'])
        ->call('removeComponent', $foreign->ulid)
        ->assertNotFound();
});

it('does not offer seasons or departures on packages', function (string $method): void {
    actingAs(userWithRole(Role::ProductManager));

    Livewire::test(ProductShow::class, ['product' => cartagenaPackage()])
        ->assertDontSee(__('catalog.seasons.add'))
        ->call($method)
        ->assertNotFound();
})->with(['addSeason', 'addDeparture']);

it('shows the package itinerary read only to agents', function (): void {
    actingAs(agent());

    Livewire::test(ProductShow::class, ['product' => cartagenaPackage()])
        ->assertSee('City tour')
        ->assertDontSee(__('catalog.components.add'))
        ->call('addComponent')
        ->assertForbidden();
});
