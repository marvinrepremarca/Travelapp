<?php

declare(strict_types=1);

use App\Modules\Crm\Actions\MoveLeadAction;
use App\Modules\Crm\Enums\InteractionType;
use App\Modules\Crm\Enums\LeadStatus;
use App\Modules\Crm\Enums\LostReason;
use App\Modules\Crm\Events\LeadWon;
use App\Modules\Crm\Exceptions\LeadRuleViolation;
use App\Modules\Crm\Livewire\LeadForm;
use App\Modules\Crm\Livewire\LeadsBoard;
use App\Modules\Crm\Livewire\LeadShow;
use App\Modules\Crm\Models\Customer;
use App\Modules\Crm\Models\Lead;
use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\SalesChannel;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires authentication', function (): void {
    get(route('crm.leads.index'))->assertRedirect(route('login'));
});

it('creates a lead owned by the agent', function (): void {
    $agent = agent();
    actingAs($agent);

    Livewire::test(LeadForm::class)
        ->set('contact_name', 'Familia Torres')
        ->set('phone', '3109876543')
        ->set('channel', SalesChannel::WhatsApp->value)
        ->set('destination', 'San Andrés')
        ->set('travel_start', '2026-12-20')
        ->set('travel_end', '2026-12-27')
        ->set('travelers_count', '4')
        ->call('save')
        ->assertHasNoErrors();

    $lead = Lead::query()->sole();
    expect($lead->status)->toBe(LeadStatus::New)
        ->and($lead->owner_id)->toBe($agent->id)
        ->and($lead->travelers_count)->toBe(4);
});

it('requires a contact and validates dates', function (): void {
    actingAs(agent());

    Livewire::test(LeadForm::class)
        ->set('contact_name', 'Sin contacto')
        ->call('save')
        ->assertHasErrors(['email' => __('crm.errors.lead_contact_required')]);

    Livewire::test(LeadForm::class)
        ->set('contact_name', 'Fechas')
        ->set('email', 'a@b.test')
        ->set('travel_start', '2026-12-20')
        ->set('travel_end', '2026-12-10')
        ->call('save')
        ->assertHasErrors('travel_end');
});

it('edits a lead', function (): void {
    $agent = agent();
    $lead = Lead::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(LeadForm::class, ['lead' => $lead])
        ->set('destination', 'Cartagena')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh()?->destination)->toBe('Cartagena');
});

it('shows the funnel by stage within the scope', function (): void {
    $agent = agent();
    Lead::factory()->ownedBy($agent)->create(['contact_name' => 'Lead nuevo']);
    Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Quoted)->create(['contact_name' => 'Lead cotizado']);
    Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Won)->create();
    Lead::factory()->ownedBy(User::factory()->create())->create(['contact_name' => 'Lead ajeno']);
    actingAs($agent);

    Livewire::test(LeadsBoard::class)
        ->assertSee('Lead nuevo')
        ->assertSee('Lead cotizado')
        ->assertDontSee('Lead ajeno')
        ->assertSee(__('crm.leads.won_count', ['count' => 1]))
        ->set('search', 'cotizado')
        ->assertDontSee('Lead nuevo');
});

it('moves a new lead to contacted when logging the first contact', function (): void {
    $agent = agent();
    $lead = Lead::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(LeadShow::class, ['lead' => $lead])
        ->set('interactionType', InteractionType::Call->value)
        ->set('interactionSummary', 'Quiere viajar en diciembre')
        ->call('logInteraction')
        ->assertHasNoErrors()
        ->assertSee('Quiere viajar en diciembre');

    expect($lead->fresh()?->status)->toBe(LeadStatus::Contacted);
});

it('keeps a new lead as new when only adding an internal note', function (): void {
    $agent = agent();
    $lead = Lead::factory()->ownedBy($agent)->create();
    actingAs($agent);

    Livewire::test(LeadShow::class, ['lead' => $lead])
        ->set('interactionType', InteractionType::Note->value)
        ->set('interactionSummary', 'Nota')
        ->call('logInteraction');

    expect($lead->fresh()?->status)->toBe(LeadStatus::New);
});

it('advances through the funnel and wins linking a customer', function (): void {
    Event::fake([LeadWon::class]);
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create();
    $lead = Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Contacted)->create();
    actingAs($agent);

    $component = Livewire::test(LeadShow::class, ['lead' => $lead])->call('move', LeadStatus::Quoted->value);
    expect($lead->fresh()?->status)->toBe(LeadStatus::Quoted);

    $component->call('move', LeadStatus::Won->value)->assertHasErrors(['customerUlid' => __('crm.errors.lead_customer_required')]);

    $component->set('customerUlid', $customer->ulid)->call('move', LeadStatus::Won->value)->assertHasNoErrors();

    expect($lead->fresh()?->status)->toBe(LeadStatus::Won)
        ->and($lead->fresh()?->customer_id)->toBe($customer->id);
    Event::assertDispatched(LeadWon::class, fn(LeadWon $event): bool => $event->customerUlid === $customer->ulid);
});

it('does not win with a customer outside the scope', function (): void {
    $agent = agent();
    $foreign = Customer::factory()->ownedBy(User::factory()->create())->create();
    $lead = Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Quoted)->create();

    expect(fn() => app(MoveLeadAction::class)->execute($lead, LeadStatus::Won, $agent, customer: $foreign))
        ->toThrow(LeadRuleViolation::class);
});

it('loses a lead only with a reason and can reopen it', function (): void {
    $agent = agent();
    $lead = Lead::factory()->ownedBy($agent)->inStatus(LeadStatus::Contacted)->create();
    actingAs($agent);

    Livewire::test(LeadShow::class, ['lead' => $lead])
        ->call('move', LeadStatus::Lost->value)
        ->assertHasErrors(['lostReason' => __('crm.errors.lost_reason_required')])
        ->set('lostReason', LostReason::Price->value)
        ->set('lostNote', 'Consiguió más barato')
        ->call('move', LeadStatus::Lost->value)
        ->assertHasNoErrors();

    expect($lead->fresh()?->lost_reason)->toBe(LostReason::Price);

    Livewire::test(LeadShow::class, ['lead' => $lead])->call('move', LeadStatus::Contacted->value);
    expect($lead->fresh()?->status)->toBe(LeadStatus::Contacted)
        ->and($lead->fresh()?->lost_reason)->toBeNull();
});

it('rejects invalid funnel transitions', function (LeadStatus $from, LeadStatus $to): void {
    $agent = agent();
    $lead = Lead::factory()->ownedBy($agent)->inStatus($from)->create();
    actingAs($agent);

    Livewire::test(LeadShow::class, ['lead' => $lead])->call('move', $to->value)->assertHasErrors();

    expect($lead->fresh()?->status)->toBe($from);
})->with([
    'new to quoted' => [LeadStatus::New, LeadStatus::Quoted],
    'won to lost' => [LeadStatus::Won, LeadStatus::Lost],
    'contacted to won' => [LeadStatus::Contacted, LeadStatus::Won],
]);

it('answers not found for leads outside the scope', function (): void {
    $lead = Lead::factory()->ownedBy(User::factory()->create())->create();
    actingAs(agent());

    get(route('crm.leads.show', $lead))->assertNotFound();
    get(route('crm.leads.edit', $lead))->assertNotFound();
});

it('labels lead enums', function (): void {
    foreach ([...LeadStatus::cases(), ...LostReason::cases(), ...InteractionType::cases()] as $case) {
        expect($case->label())->not->toStartWith('crm.');
    }

    expect(LeadStatus::Won->isOpen())->toBeFalse()
        ->and(LeadStatus::Quoted->tone()->value)->toBe('warning')
        ->and(LeadRuleViolation::contactRequired()->errorCode())->toBe('invalid_lead');
});

it('grows each funnel stage with show more', function (): void {
    config()->set('travel.crm.board_column_size', 2);
    $agent = agent();
    Lead::factory()->ownedBy($agent)->count(3)->create();
    actingAs($agent);

    Livewire::test(LeadsBoard::class)
        ->assertSee(__('crm.leads.showing', ['shown' => 2, 'total' => 3]))
        ->assertSee(__('crm.leads.show_more', ['stage' => LeadStatus::New->label()]))
        ->call('showMore', 'not-a-stage')
        ->assertSee(__('crm.leads.showing', ['shown' => 2, 'total' => 3]))
        ->call('showMore', LeadStatus::New->value)
        ->assertSee(__('crm.leads.showing', ['shown' => 3, 'total' => 3]))
        ->assertDontSee(__('crm.leads.show_more', ['stage' => LeadStatus::New->label()]))
        ->set('search', 'x')
        ->assertSet('pages', []);
});
