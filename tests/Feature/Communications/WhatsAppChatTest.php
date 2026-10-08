<?php

declare(strict_types=1);

use App\Modules\Communications\Actions\SendAgentReplyAction;
use App\Modules\Communications\Actions\TakeConversationAction;
use App\Modules\Communications\Enums\BotStep;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Enums\MessageAuthor;
use App\Modules\Communications\Enums\MessageStatus;
use App\Modules\Communications\Exceptions\ConversationRuleViolation;
use App\Modules\Communications\Livewire\ConversationsInbox;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Communications\Services\PhoneNumbers;
use App\Modules\Crm\Models\Lead;
use App\Modules\Integrations\Adapters\FakeWhatsApp\FakeWhatsAppChannel;
use App\Modules\Integrations\Livewire\WhatsAppSimulator;
use App\Modules\Shared\Enums\SalesChannel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

const CUSTOMER_PHONE = '+57 300 555 1234';

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
});

/** El cliente escribe desde el simulador (mismo webhook firmado que el proveedor real). */
function customerSays(string ...$texts): void
{
    $simulator = Livewire::test(WhatsAppSimulator::class)->set('phone', CUSTOMER_PHONE)->set('name', 'Laura');
    foreach ($texts as $text) {
        $simulator->set('text', $text)->call('send')->assertHasNoErrors();
    }
}

function conversation(): Conversation
{
    return Conversation::query()->latest('id')->firstOrFail();
}

it('runs the guided quote bot and hands the request to an advisor', function (): void {
    actingAs(agent());

    customerSays('Hola', 'Laura Pérez', 'Cartagena', '10/11/2026', '15/11/2026', '3 personas');

    $conversation = conversation();
    expect($conversation->status)->toBe(ConversationStatus::WaitingAgent)
        ->and($conversation->bot_step)->toBe(BotStep::Done)
        ->and($conversation->contact_name)->toBe('Laura Pérez')
        ->and($conversation->bot_data)->toMatchArray([
            'destination' => 'Cartagena', 'departure' => '2026-11-10', 'return' => '2026-11-15', 'travelers' => 3,
        ])
        ->and($conversation->messages()->where('author', MessageAuthor::Bot)->count())->toBe(6)
        ->and($conversation->messages()->where('author', MessageAuthor::Bot)->where('status', MessageStatus::Sent)->count())->toBe(6);

    Livewire::test(WhatsAppSimulator::class)->set('phone', CUSTOMER_PHONE)
        ->assertSee(__('communications.bot.ask.destination'))
        ->assertSee('Viajeros: 3');
});

it('asks again on invalid answers and accepts one way trips', function (): void {
    actingAs(agent());

    customerSays('Hola', 'Laura', 'San Andrés', '32/13/2026', '01/09/2026', '10/11/2026', 'no', 'cien', '2');

    $conversation = conversation();
    expect($conversation->status)->toBe(ConversationStatus::WaitingAgent)
        ->and($conversation->bot_data['return'])->toBeNull()
        ->and($conversation->bot_data['travelers'])->toBe(2)
        ->and($conversation->messages()->where('body', 'like', '%' . mb_substr(__('communications.bot.invalid.travelers', ['max' => 9]), 0, 20) . '%')->count())->toBe(1);
});

it('lets the customer ask for an advisor at any step', function (): void {
    actingAs(agent());

    customerSays('Hola', 'Asesor');

    expect(conversation()->status)->toBe(ConversationStatus::WaitingAgent)
        ->and(ConversationMessage::query()->latest('id')->firstOrFail()->body)->toBe(__('communications.bot.handoff'));
});

it('authenticates and deduplicates webhooks', function (): void {
    $webhook = app(FakeWhatsAppChannel::class)->webhookFor(CUSTOMER_PHONE, 'Hola', null);
    $url = route('communications.webhook', ['channel' => FakeWhatsAppChannel::KEY]);

    $this->call('POST', $url, [], [], [], ['HTTP_X_FAKE_WHATSAPP_SIGNATURE' => 'mala', 'CONTENT_TYPE' => 'application/json'], $webhook['body'])->assertStatus(400);
    $this->call('POST', $url, [], [], [], ['HTTP_X_FAKE_WHATSAPP_SIGNATURE' => $webhook['headers'][FakeWhatsAppChannel::SIGNATURE_HEADER], 'CONTENT_TYPE' => 'application/json'], $webhook['body'])->assertStatus(202);
    $this->call('POST', $url, [], [], [], ['HTTP_X_FAKE_WHATSAPP_SIGNATURE' => $webhook['headers'][FakeWhatsAppChannel::SIGNATURE_HEADER], 'CONTENT_TYPE' => 'application/json'], $webhook['body'])->assertStatus(202);
    postJson(route('communications.webhook', ['channel' => 'desconocido']), [])->assertStatus(400);

    expect(ConversationMessage::query()->where('author', MessageAuthor::Customer)->count())->toBe(1);
});

it('keeps the phone encrypted and masked', function (): void {
    actingAs(agent());
    customerSays('Hola');

    $raw = DB::table('conversations')->value('contact_phone');

    expect($raw)->not->toContain('3005551234')
        ->and(conversation()->contact_phone)->toBe('+573005551234')
        ->and(conversation()->contact_phone_hash)->toBe(app(PhoneNumbers::class)->hash('300 555 1234'))
        ->and(app(PhoneNumbers::class)->mask(CUSTOMER_PHONE))->toBe(__('communications.masked_phone', ['last' => '1234']));
});

it('lets an advisor take the conversation, creating the WhatsApp lead, and reply', function (): void {
    $advisor = agent();
    actingAs($advisor);
    customerSays('Hola', 'Laura Pérez', 'Cartagena', '10/11/2026', 'no', '2');
    $ulid = conversation()->ulid;

    Livewire::test(ConversationsInbox::class)
        ->assertSee('Laura Pérez')
        ->call('select', $ulid)
        ->assertSee('Cartagena')
        ->call('take')
        ->assertHasNoErrors()
        ->call('send')
        ->assertHasErrors('reply')
        ->set('reply', 'Te envío opciones en un momento')
        ->call('send')
        ->assertHasNoErrors()
        ->assertSee('Te envío opciones en un momento');

    $conversation = conversation();
    $lead = Lead::query()->where('ulid', $conversation->lead_ulid)->sole();
    expect($conversation->status)->toBe(ConversationStatus::WithAgent)
        ->and($conversation->owner_id)->toBe($advisor->id)
        ->and($lead->channel)->toBe(SalesChannel::WhatsApp)
        ->and($lead->owner_id)->toBe($advisor->id)
        ->and($lead->destination)->toBe('Cartagena')
        ->and($lead->travelers_count)->toBe(2)
        ->and($conversation->messages()->where('author', MessageAuthor::System)->count())->toBe(1);

    // El cliente ve la respuesta del asesor en su WhatsApp.
    Livewire::test(WhatsAppSimulator::class)->set('phone', CUSTOMER_PHONE)->assertSee('Te envío opciones en un momento');
});

it('prevents taking or answering conversations of another advisor', function (): void {
    actingAs(agent());
    customerSays('Hola', 'Asesor');
    $ulid = conversation()->ulid;
    app(TakeConversationAction::class)->execute(agent(), $ulid);
    $other = agent();

    expect(fn() => app(TakeConversationAction::class)->execute($other, $ulid))->toThrow(ConversationRuleViolation::class, __('communications.errors.already_taken'))
        ->and(fn() => app(SendAgentReplyAction::class)->execute($other, $ulid, 'Hola'))->toThrow(ConversationRuleViolation::class, __('communications.errors.not_yours'));

    actingAs($other);
    Livewire::test(ConversationsInbox::class)
        ->set('filter', ConversationsInbox::FILTER_OPEN)
        ->assertDontSee('Contacto')
        ->set('conversation', $ulid)
        ->assertSee(__('communications.select_title'));
});

it('closes the conversation and opens a new one with the bot when the customer writes again', function (): void {
    $advisor = agent();
    actingAs($advisor);
    customerSays('Hola', 'Asesor');
    $first = conversation();
    app(TakeConversationAction::class)->execute($advisor, $first->ulid);

    Livewire::test(ConversationsInbox::class)->call('select', $first->ulid)->call('close')->assertHasNoErrors()
        ->set('filter', ConversationsInbox::FILTER_CLOSED)->assertSee(__('communications.status.closed'));
    customerSays('Hola de nuevo');

    expect($first->fresh()?->status)->toBe(ConversationStatus::Closed)
        ->and(conversation()->id)->not->toBe($first->id)
        ->and(conversation()->status)->toBe(ConversationStatus::Bot)
        ->and(fn() => app(SendAgentReplyAction::class)->execute($advisor, $first->ulid, 'x'))->toThrow(ConversationRuleViolation::class, __('communications.errors.closed'))
        ->and(fn() => app(TakeConversationAction::class)->execute($advisor, $first->ulid))->toThrow(ConversationRuleViolation::class);
});

it('shows the inbox filters and reports actions on stale conversations', function (): void {
    actingAs(agent());
    customerSays('Hola');

    Livewire::test(ConversationsInbox::class)
        ->assertSee(__('communications.select_title'))
        ->set('filter', ConversationsInbox::FILTER_MINE)
        ->assertSee(__('communications.empty'))
        ->set('filter', ConversationsInbox::FILTER_WAITING)
        ->call('select', conversation()->ulid)
        ->call('take')
        ->call('take')
        ->assertHasErrors('conversation');
});

it('hides the simulator when disabled', function (): void {
    config()->set('travel.communications.simulator_enabled', false);

    actingAs(agent())->get(route('integrations.whatsapp-simulator'))->assertNotFound();
});

it('labels communication enums', function (): void {
    foreach ([...ConversationStatus::cases(), ...MessageAuthor::cases(), ...MessageStatus::cases()] as $case) {
        expect($case->label())->not->toStartWith('communications.');
    }
});

it('goes from the chat to a quote creating the customer from the lead', function (): void {
    $advisor = agent();
    actingAs($advisor);
    customerSays('Hola', 'Laura Pérez', 'Cartagena', '10/11/2026', 'no', '2');
    $ulid = conversation()->ulid;

    Livewire::test(ConversationsInbox::class)
        ->call('select', $ulid)
        ->call('quote')
        ->assertHasErrors('conversation')
        ->call('take')
        ->call('quote')
        ->assertRedirect(route('customers.create', ['lead' => conversation()->lead_ulid]));

    $lead = Lead::query()->where('ulid', conversation()->lead_ulid)->sole();
    Livewire::withQueryParams(['lead' => $lead->ulid])->test(App\Modules\Customers\Livewire\CustomerForm::class)
        ->assertSet('first_name', 'Laura')
        ->assertSet('last_name', 'Pérez')
        ->assertSet('phone', '+573005551234')
        ->set('document_number', '1020304050')
        ->set('consent_data_processing', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirectContains('title=Viaje%20a%20Cartagena');

    $customer = App\Modules\Customers\Models\Customer::query()->where('id', $lead->fresh()?->customer_id)->sole();
    Livewire::test(ConversationsInbox::class)
        ->call('select', $ulid)
        ->call('quote')
        ->assertRedirect(route('quotes.create', ['customer' => $customer->ulid, 'title' => 'Viaje a Cartagena', 'channel' => SalesChannel::WhatsApp->value]));

    Livewire::withQueryParams(['customer' => $customer->ulid, 'title' => 'Viaje a Cartagena', 'channel' => 'whatsapp'])
        ->test(App\Modules\Quotes\Livewire\QuoteCreate::class)
        ->assertSet('title', 'Viaje a Cartagena')
        ->assertSet('sales_channel', 'whatsapp')
        ->call('save')
        ->assertHasNoErrors();
    expect(App\Modules\Quotes\Models\Quote::query()->sole()->customer_id)->toBe($customer->id);
});
