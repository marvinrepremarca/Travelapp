<?php

declare(strict_types=1);

use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Livewire\LeadShow;
use App\Modules\Crm\Models\Lead;
use App\Modules\Customers\Models\Customer;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AcceptQuoteAction;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\AcceptanceChannel;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Enums\QuoteStatus;
use App\Modules\Quotes\Livewire\QuoteShow;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    $this->seed(TaxReferenceSeeder::class);
    MarkupRule::factory()->percentage(1000)->create();
});

/** Enciende solo esta capacidad sobre el núcleo (ADR-0007). */
function onlyCapability(Capability $only): void
{
    foreach (Capability::cases() as $capability) {
        config()->set("capabilities.enabled.{$capability->value}", $capability === $only);
    }

    app()->forgetInstance(Capabilities::class);
    expect(app(Capabilities::class)->problems())->toBe([]);
}

it('runs the whole quoting flow with only quoting on', function (): void {
    onlyCapability(Capability::Quoting);
    $agent = agent();
    actingAs($agent);

    $quote = app(CreateQuoteAction::class)->execute($agent, Customer::factory()->ownedBy($agent)->create(), 'Cartagena en pareja', 'COP', SalesChannel::Branch);
    app(AddItemAction::class)->execute($quote, $quote->options()->firstOrFail(), new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [35, 33],
        nights: 3,
        productType: ProductType::Hotel,
        description: 'Hotel Caribe 3 noches',
        manualNet: Money::of('900000', 'COP'),
    ));
    app(SendQuoteAction::class)->execute($quote, $agent, CarbonImmutable::now());
    $accepted = app(AcceptQuoteAction::class)->execute($quote, $quote->options()->firstOrFail()->ulid, AcceptanceChannel::Agent, null, CarbonImmutable::now());

    expect($accepted->status)->toBe(QuoteStatus::Accepted);
    actingAs($agent)->get(route('quotes.index'))->assertOk();
    actingAs($agent)->get(route('quotes.create'))->assertOk();
    actingAs($agent)->get(route('search.flights'))->assertOk();
    actingAs($agent)->get(route('dashboard'))->assertOk();
    actingAs($agent)->get(route('crm.leads.index'))->assertNotFound();
    actingAs($agent)->get(route('bookings.index'))->assertNotFound();
    Livewire::test(QuoteShow::class, ['quote' => $accepted])->assertOk()->assertDontSee(route('bookings.from-quote', $accepted->ulid));
});

it('runs the whole commercial funnel with only commercial on', function (): void {
    onlyCapability(Capability::Commercial);
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    $lead = Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Contacted)->create();
    actingAs($agent);

    // La etapa «Cotizado» del embudo es manual: no necesita la capacidad Cotizaciones.
    Livewire::test(LeadShow::class, ['lead' => $lead])
        ->call('move', LeadStatus::Quoted->value)
        ->set('customerUlid', $customer->ulid)
        ->call('move', LeadStatus::Won->value)
        ->assertHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Won);
    actingAs($agent)->get(route('crm.leads.index'))->assertOk();
    actingAs($agent)->get(route('crm.leads.create'))->assertOk();
    actingAs($agent)->get(route('customers.create', ['lead' => Lead::factory()->ownedBy($agent)->create()->ulid]))->assertOk();
    actingAs($agent)->get(route('dashboard'))->assertOk();
    actingAs($agent)->get(route('quotes.index'))->assertNotFound();
});
