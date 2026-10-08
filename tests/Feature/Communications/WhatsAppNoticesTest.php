<?php

declare(strict_types=1);

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Communications\Actions\SendBalanceRemindersAction;
use App\Modules\Communications\Enums\ConversationStatus;
use App\Modules\Communications\Enums\MessageStatus;
use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Models\Conversation;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Customers\Actions\RecordConsentAction;
use App\Modules\Customers\Enums\ConsentChannel;
use App\Modules\Customers\Enums\ConsentPurpose;
use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\CreatePaymentLinkAction;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Actions\ValidateTransferAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Actions\AddItemAction;
use App\Modules\Quotes\Actions\CreateQuoteAction;
use App\Modules\Quotes\Actions\SendQuoteAction;
use App\Modules\Quotes\Data\QuoteItemData;
use App\Modules\Quotes\Enums\QuoteItemKind;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Enums\SalesChannel;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

function consenting(Customer $customer, bool $granted = true): Customer
{
    app(RecordConsentAction::class)->execute($customer, ConsentPurpose::DataProcessing, $granted, ConsentChannel::WhatsApp, User::query()->findOrFail($customer->owner_id));

    return $customer;
}

function notices(NoticeTemplate $template): \Illuminate\Database\Eloquent\Collection
{
    return ConversationMessage::query()->where('template', $template->value)->get();
}

/** Cotización con un hotel enviada al cliente. */
function sendQuoteTo(Customer $customer, User $agent): App\Modules\Quotes\Models\Quote
{
    $quote = app(CreateQuoteAction::class)->execute($agent, $customer, 'Cartagena en familia', 'COP', SalesChannel::WhatsApp);
    app(AddItemAction::class)->execute($quote, $quote->options()->firstOrFail(), new QuoteItemData(
        kind: QuoteItemKind::Manual,
        serviceDate: CarbonImmutable::parse('2026-11-10'),
        passengerAges: [40],
        nights: 2,
        productType: ProductType::Hotel,
        description: 'Hotel Caribe',
        manualNet: Money::of('500000', 'COP'),
    ));
    app(SendQuoteAction::class)->execute($quote, $agent, CarbonImmutable::now());

    return $quote->fresh() ?? $quote;
}

function bookingWithConsent(): Booking
{
    $booking = familyBooking(agent());
    consenting(Customer::query()->findOrFail($booking->customer_id));

    return $booking;
}

it('sends the quote link by WhatsApp in a conversation owned by the advisor', function (): void {
    $agent = agent();
    $customer = consenting(Customer::factory()->ownedBy($agent)->create(['phone' => '300 111 2233']));

    $quote = sendQuoteTo($customer, $agent);
    QuoteSent::dispatch($quote->ulid, $quote->current_version);

    $notice = notices(NoticeTemplate::QuoteSent)->sole();
    expect($notice->body)->toContain((string) $quote->number)
        ->and($notice->body)->toContain('quote-link')
        ->and($notice->status)->toBe(MessageStatus::Sent)
        ->and($notice->conversation->owner_id)->toBe($agent->id)
        ->and($notice->conversation->status)->toBe(ConversationStatus::WithAgent);
});

it('sends nothing without phone or data processing consent', function (bool $withPhone, bool $granted): void {
    $agent = agent();
    $customer = Customer::factory()->ownedBy($agent)->create(['phone' => $withPhone ? '300 111 2233' : null]);
    consenting($customer, $granted);

    sendQuoteTo($customer, $agent);

    expect(ConversationMessage::query()->count())->toBe(0);
})->with([
    'no phone' => [false, true],
    'consent revoked' => [true, false],
]);

it('sends payment links and receipts for approved payments only', function (): void {
    $booking = bookingWithConsent();
    $owner = User::query()->findOrFail($booking->owner_id);
    $account = app(BookingAccounts::class)->account($booking->ulid);

    app(CreatePaymentLinkAction::class)->execute($owner, $account, Money::of('100000', 'COP'), CarbonImmutable::now());
    app(RecordPaymentAction::class)->execute($owner, $account, PaymentMethod::Cash, Money::of('50000', 'COP'), null, null, CarbonImmutable::now());
    $transfer = app(RecordPaymentAction::class)->execute($owner, $account, PaymentMethod::BankTransfer, Money::of('70000', 'COP'), 'TRX-1', null, CarbonImmutable::now());

    expect(notices(NoticeTemplate::PaymentLink))->toHaveCount(1)
        ->and(notices(NoticeTemplate::PaymentLink)->sole()->body)->toContain('fake-checkout')
        ->and(notices(NoticeTemplate::PaymentReceived))->toHaveCount(1);

    app(ValidateTransferAction::class)->execute(financeUser(), $transfer, true, null, CarbonImmutable::now());

    expect(notices(NoticeTemplate::PaymentReceived))->toHaveCount(2)
        ->and(Conversation::query()->count())->toBe(1);
});

it('reminds the pending balance once, days before it is due', function (): void {
    // Primer servicio 2026-11-10; el saldo vence 15 días antes (2026-10-26); se recuerda 3 días antes.
    $booking = bookingWithConsent();
    $paid = bookingWithConsent();
    $owner = User::query()->findOrFail($paid->owner_id);
    $account = app(BookingAccounts::class)->account($paid->ulid);
    app(RecordPaymentAction::class)->execute($owner, $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());
    $remind = app(SendBalanceRemindersAction::class);

    expect($remind->execute(CarbonImmutable::parse('2026-10-22')))->toBe(0)
        ->and($remind->execute(CarbonImmutable::parse('2026-10-23')))->toBe(1)
        ->and($remind->execute(CarbonImmutable::parse('2026-10-23')))->toBe(0)
        ->and(notices(NoticeTemplate::BalanceReminder)->sole()->body)->toContain((string) $booking->number);
});

it('adds notices to the open conversation the customer already has', function (): void {
    $booking = bookingWithConsent();
    $customer = Customer::query()->findOrFail($booking->customer_id);
    $owner = User::query()->findOrFail($booking->owner_id);
    $conversation = new Conversation();
    $conversation->forceFill([
        'channel' => 'fake_whatsapp',
        'contact_phone' => app(App\Modules\Communications\Services\PhoneNumbers::class)->normalize((string) $customer->phone),
        'contact_phone_hash' => app(App\Modules\Communications\Services\PhoneNumbers::class)->hash((string) $customer->phone),
        'status' => ConversationStatus::Bot,
        'bot_data' => [],
        'last_message_at' => CarbonImmutable::now(),
    ])->save();

    app(RecordPaymentAction::class)->execute($owner, app(BookingAccounts::class)->account($booking->ulid), PaymentMethod::Cash, Money::of('1000', 'COP'), null, null, CarbonImmutable::now());

    expect(notices(NoticeTemplate::PaymentReceived)->sole()->conversation_id)->toBe($conversation->id);
});
