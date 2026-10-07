<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddDepartureAction;
use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Models\CatalogDeparture;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Enums\Role;
use App\Modules\Portal\Livewire\ShopIndex;
use App\Modules\Portal\Livewire\ShopProductPage;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-01 08:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Tour publicado con tarifa de adulto de 100.000 COP y una salida abierta de 10 cupos. */
function shopTour(int $capacity = 10): CatalogDeparture
{
    $product = CatalogProduct::factory()->create(['name' => 'Tour Islas del Rosario', 'description' => 'Día de playa en el archipiélago.']);
    app(AddSeasonAction::class)->execute($product, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 10000000, 'child' => 6000000]);

    return app(AddDepartureAction::class)->execute($product, CarbonImmutable::parse('2026-11-20'), '08:00', $capacity);
}

it('lists published products with open departures and a from price', function (): void {
    $departure = shopTour();
    CatalogProduct::factory()->create(['name' => 'Producto sin salidas']);

    Livewire::test(ShopIndex::class)
        ->assertSee('Tour Islas del Rosario')
        ->assertSee(__('portal.shop.from'))
        ->assertSee(trans_choice('portal.shop.departures_count', 1, ['count' => 1]))
        ->assertDontSee('Producto sin salidas');

    $departure->product->is_active = false;
    $departure->product->save();
    Livewire::test(ShopIndex::class)->assertSee(__('portal.shop.empty'));
});

it('turns a shop request into an online lead for an advisor with consent and an estimate', function (): void {
    $departure = shopTour();
    $advisor = userWithRole(Role::TravelAgent);

    Livewire::test(ShopProductPage::class, ['product' => $departure->product->ulid])
        ->assertSet('departure', $departure->ulid)
        ->set('seats', '2')
        ->call('request')
        ->assertHasErrors(['contactName', 'email', 'phone', 'acceptsDataProcessing'])
        ->set('contactName', 'Ana Viajera')
        ->set('email', 'ana@example.test')
        ->set('phone', '+57 300 555 1234')
        ->set('acceptsDataProcessing', true)
        ->call('request')
        ->assertHasNoErrors()
        ->assertSet('requested', true)
        ->assertSee(__('portal.shop.requested'));

    $lead = Lead::query()->sole();
    expect($lead->owner_id)->toBe($advisor->id)
        ->and($lead->channel)->toBe(SalesChannel::Online)
        ->and($lead->travelers_count)->toBe(2)
        ->and($lead->notes)->toContain('Tour Islas del Rosario')
        ->and($lead->notes)->toContain(config('travel.privacy.policy_version'));
});

it('refuses requests above the available seats and unknown products', function (): void {
    $departure = shopTour(capacity: 3);
    userWithRole(Role::TravelAgent);

    Livewire::test(ShopProductPage::class, ['product' => $departure->product->ulid])
        ->set('seats', '4')
        ->set('contactName', 'Ana Viajera')
        ->set('email', 'ana@example.test')
        ->set('phone', '3005551234')
        ->set('acceptsDataProcessing', true)
        ->call('request')
        ->assertHasErrors('request')
        ->assertSee(__('portal.shop.errors.not_enough_seats', ['available' => 3]));

    expect(Lead::query()->count())->toBe(0);
    get(route('portal.shop.product', 'no-existe'))->assertNotFound();
    get(route('portal.shop'))->assertOk();
});

it('limits shop requests per hour', function (): void {
    $departure = shopTour();
    userWithRole(Role::TravelAgent);
    config()->set('travel.portal.shop_requests_per_hour', 1);
    $page = Livewire::test(ShopProductPage::class, ['product' => $departure->product->ulid])
        ->set('contactName', 'Ana Viajera')
        ->set('email', 'ana@example.test')
        ->set('phone', '3005551234')
        ->set('acceptsDataProcessing', true)
        ->call('request')
        ->assertHasNoErrors();

    $page->set('contactName', 'Otra persona')->set('email', 'otra@example.test')->set('phone', '3005551234')->set('acceptsDataProcessing', true)->call('request');

    $page->assertHasErrors('request');
    expect(Lead::query()->count())->toBe(1);
});
