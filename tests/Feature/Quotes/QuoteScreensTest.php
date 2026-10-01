<?php

declare(strict_types=1);

use App\Modules\Catalog\Actions\AddSeasonAction;
use App\Modules\Catalog\Models\CatalogProduct;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Livewire\QuoteCreate;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Quotes\Livewire\QuotesIndex;
use App\Modules\Quotes\Models\Quote;
use App\Modules\Shared\Enums\ProductType;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function quoteOf(User $agent): Quote
{
    return Quote::factory()->ownedBy($agent)->create(['customer_id' => Customer::factory()->ownedBy($agent)]);
}

function seasonedTour(): CatalogProduct
{
    $tour = CatalogProduct::factory()->create(['name' => 'Tour murallas']);
    app(AddSeasonAction::class)->execute($tour, 'Todo el año', CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2027-12-31'), ['adult' => 10000000]);

    return $tour;
}

it('requires login and hides quotes outside the scope with 404', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);

    get(route('quotes.index'))->assertRedirect(route('login'));
    actingAs($agent)->get(route('quotes.show', $quote))->assertOk()->assertSee($quote->number);
    actingAs(agent())->get(route('quotes.show', $quote))->assertNotFound();
    actingAs(userWithRole(Role::AgencyOwner))->get(route('quotes.show', $quote))->assertOk();
});

it('lists only quotes in scope and filters them', function (): void {
    $agent = agent();
    $mine = quoteOf($agent);
    $other = quoteOf(agent());
    $mine->update(['title' => 'Viaje a San Andrés']);
    actingAs($agent);

    Livewire::test(QuotesIndex::class)->assertSee($mine->number)->assertDontSee($other->number);
    Livewire::test(QuotesIndex::class)->set('status', QuoteStatus::Sent->value)->assertSee(__('quotes.empty_title'));
    Livewire::test(QuotesIndex::class)->set('status', 'spaceship')->set('search', 'san andrés')->assertSee($mine->number);
    Livewire::test(QuotesIndex::class)->set('search', $mine->customer->display_name)->assertSee($mine->number);
});

it('creates a quote only for customers in scope', function (): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['display_name' => 'Familia Pérez']);
    $foreign = Customer::factory()->ownedBy(agent())->create(['display_name' => 'Familia Ajena']);
    actingAs($agent);

    Livewire::test(QuoteCreate::class)
        ->set('customerSearch', 'familia')
        ->assertSee('Familia Pérez')
        ->assertDontSee('Familia Ajena')
        ->call('chooseCustomer', $foreign->ulid)
        ->set('title', 'Cartagena')
        ->call('save')
        ->assertHasErrors('customer_ulid')
        ->call('chooseCustomer', $customer->ulid)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $quote = Quote::query()->sole();
    expect($quote->owner_id)->toBe($agent->id)
        ->and($quote->customer_id)->toBe($customer->id);
});

it('validates new quotes', function (): void {
    actingAs(agent());

    Livewire::test(QuoteCreate::class)
        ->set('sale_currency', '')
        ->set('sales_channel', 'pigeon')
        ->call('save')
        ->assertHasErrors(['customer_ulid', 'title', 'sale_currency', 'sales_channel']);
});

it('builds, sends and accepts a quote from its screen', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);
    $tour = seasonedTour();
    actingAs($agent);

    $screen = Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->set('item.kind', QuoteItemKind::Catalog->value)
        ->set('item.catalog_product', $tour->ulid)
        ->set('item.service_date', '2026-11-10')
        ->set('item.ages', '35, 33')
        ->call('addItem')
        ->assertHasNoErrors()
        ->assertSee('Tour murallas')
        ->set('item.kind', QuoteItemKind::Manual->value)
        ->set('item.product_type', ProductType::Hotel->value)
        ->set('item.description', 'Hotel Caribe')
        ->set('item.net_amount', '500000')
        ->set('item.service_date', '2026-11-10')
        ->set('item.nights', '2')
        ->set('item.ages', '35,33')
        ->call('addItem')
        ->assertHasNoErrors()
        ->assertSee('Hotel Caribe')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee(__('quotes.status.sent'))
        ->assertDontSee(__('quotes.items.add'));

    $option = $quote->options()->firstOrFail();
    $screen->set('acceptance.option', $option->ulid)->set('acceptance.note', 'Aceptó por WhatsApp')->call('accept')->assertHasNoErrors();

    expect($quote->fresh()?->status)->toBe(QuoteStatus::Accepted);
});

it('validates items and shows business rule errors next to the form', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);
    $tour = CatalogProduct::factory()->create();
    actingAs($agent);

    Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->set('item.kind', QuoteItemKind::Manual->value)
        ->set('item.service_date', '2026-09-01')
        ->set('item.nights', '-1')
        ->set('item.ages', 'treinta')
        ->call('addItem')
        ->assertHasErrors(['item.product_type', 'item.description', 'item.net_amount', 'item.service_date', 'item.nights', 'item.ages'])
        ->set('item.kind', QuoteItemKind::Catalog->value)
        ->set('item.catalog_product', $tour->ulid)
        ->set('item.service_date', '2026-11-10')
        ->set('item.nights', '0')
        ->set('item.ages', '35, 200')
        ->call('addItem')
        ->assertHasErrors('item.ages.1')
        ->set('item.ages', '35')
        ->call('addItem')
        ->assertHasErrors('item.service_date');
});

it('manages options and refuses to send empty ones', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);
    actingAs($agent);

    $screen = Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->call('send')
        ->assertHasErrors('quote')
        ->set('newOptionTitle', 'Plan económico')
        ->call('addOption')
        ->assertHasNoErrors()
        ->assertSee(__('quotes.options.label', ['label' => 'B']));

    $optionB = $quote->options()->where('label', 'B')->firstOrFail();
    $screen->call('removeOption', $optionB->ulid)->assertHasNoErrors();
    $screen->call('removeOption', $quote->options()->firstOrFail()->ulid)->assertHasErrors('option');
    expect($quote->options()->count())->toBe(1);
});

it('revises and cancels quotes from the screen', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);
    seasonedTour();
    actingAs($agent);
    $screen = Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->set('item.catalog_product', CatalogProduct::query()->value('ulid'))
        ->set('item.service_date', '2026-11-10')
        ->set('item.ages', '40')
        ->call('addItem')
        ->call('send');

    $screen->call('revise')->assertSee(__('quotes.status.draft'));
    $screen->call('cancel')->assertSee(__('quotes.status.cancelled'));
    $screen->call('send')->assertHasErrors('quote');

    expect($quote->fresh()?->status)->toBe(QuoteStatus::Cancelled);
});

it('hides margins from agents unless they may see them', function (): void {
    $agent = agent();
    $quote = quoteOf($agent);
    $tour = seasonedTour();
    actingAs($agent);
    Livewire::test(QuoteShow::class, ['quote' => $quote])
        ->set('item.catalog_product', $tour->ulid)
        ->set('item.service_date', '2026-11-10')
        ->set('item.ages', '40')
        ->call('addItem')
        ->assertDontSee(__('quotes.options.margin'));

    actingAs(userWithRole(Role::AgencyOwner));
    Livewire::test(QuoteShow::class, ['quote' => $quote])->assertSee(__('quotes.options.margin'));
});

it('does not touch items or options of other quotes', function (string $method): void {
    $agent = agent();
    $other = quoteOf($agent);
    $tour = seasonedTour();
    actingAs($agent);
    Livewire::test(QuoteShow::class, ['quote' => $other])
        ->set('item.catalog_product', $tour->ulid)
        ->set('item.service_date', '2026-11-10')
        ->set('item.ages', '40')
        ->call('addItem');
    $foreignUlid = $method === 'removeItem' ? App\Modules\Quotes\Models\QuoteItem::query()->value('ulid') : $other->options()->value('ulid');

    Livewire::test(QuoteShow::class, ['quote' => quoteOf($agent)])->call($method, $foreignUlid)->assertNotFound();
})->with(['removeItem', 'removeOption']);
