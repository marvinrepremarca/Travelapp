<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Finance\Listeners\RegisterSupplierPayable;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Quotes\Events\QuoteSent;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\CatchUpPolicy;
use App\Modules\Shared\Enums\DeliveryOutcome;
use App\Modules\Shared\IntegrationEvents\CapabilitySubscriptions;
use App\Modules\Shared\IntegrationEvents\CatchUpIntegrationEvents;
use App\Modules\Shared\IntegrationEvents\EventSerializer;
use App\Modules\Shared\IntegrationEvents\IntegrationEventDelivery;
use App\Modules\Shared\IntegrationEvents\StoredIntegrationEvent;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;

use function Pest\Laravel\artisan;

/** Listener de prueba que anota cada aviso recibido. */
final class RecordingQuoteListener
{
    /** @var list<string> */
    public static array $handled = [];

    public function handle(QuoteSent $event): void
    {
        self::$handled[] = $event->quoteUlid;
    }
}

function enableCapabilities(Capability ...$capabilities): void
{
    foreach ($capabilities as $capability) {
        config()->set("capabilities.enabled.{$capability->value}", true);
    }

    app()->forgetInstance(Capabilities::class);
}

/** Hotel del expediente de familia con un proveedor a crédito, confirmado. */
function confirmHotelWithCreditSupplier(): void
{
    MarkupRule::factory()->percentage(1000)->create();
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    $hotel->supplier_id = Supplier::factory()->create(['payment_terms' => PaymentTerms::Credit, 'payment_days' => 30])->id;
    $hotel->save();

    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
}

beforeEach(function (): void {
    RecordingQuoteListener::$handled = [];
});

it('stores every integration event in the outbox with its payload', function (): void {
    (new QuoteSent('01QUOTE', 2))->publish();

    $stored = StoredIntegrationEvent::query()->sole();

    expect($stored->name)->toBe(QuoteSent::NAME)
        ->and($stored->payload)->toEqual(['quoteUlid' => '01QUOTE', 'version' => 2]);
});

it('records accounting deliveries live while the capability is on', function (): void {
    confirmHotelWithCreditSupplier();

    expect(SupplierPayable::query()->count())->toBe(1)
        ->and(IntegrationEventDelivery::query()->where('subscriber', RegisterSupplierPayable::class . '@handle')->sole()->outcome)
        ->toBe(DeliveryOutcome::Delivered);
});

it('recognizes the sales made while accounting was off once it is turned on again', function (): void {
    disableCapabilities(Capability::Accounting);
    confirmHotelWithCreditSupplier();
    expect(SupplierPayable::query()->count())->toBe(0);

    enableCapabilities(Capability::Accounting);
    $processed = app(CatchUpIntegrationEvents::class)->run();

    expect($processed)->toBe(1)
        ->and(SupplierPayable::query()->count())->toBe(1);
});

it('never processes an event twice when catching up repeatedly', function (): void {
    disableCapabilities(Capability::Accounting);
    confirmHotelWithCreditSupplier();
    enableCapabilities(Capability::Accounting);

    app(CatchUpIntegrationEvents::class)->run();
    $second = app(CatchUpIntegrationEvents::class)->run();

    expect($second)->toBe(0)
        ->and(SupplierPayable::query()->count())->toBe(1);
});

it('skips customer notices while messaging is off and does not send them later', function (): void {
    app(CapabilitySubscriptions::class)->listen(Capability::Messaging, QuoteSent::class, RecordingQuoteListener::class, CatchUpPolicy::Skip);
    disableCapabilities(Capability::Messaging);

    (new QuoteSent('01QUOTE', 1))->publish();
    enableCapabilities(Capability::Messaging);
    app(CatchUpIntegrationEvents::class)->run();

    expect(RecordingQuoteListener::$handled)->toBe([])
        ->and(IntegrationEventDelivery::query()->where('subscriber', RecordingQuoteListener::class . '@handle')->sole()->outcome)
        ->toBe(DeliveryOutcome::Skipped);
});

it('replays pending events in the order they happened', function (): void {
    app(CapabilitySubscriptions::class)->listen(Capability::Compliance, QuoteSent::class, RecordingQuoteListener::class, CatchUpPolicy::Replay);
    disableCapabilities(Capability::Compliance);

    (new QuoteSent('01FIRST', 1))->publish();
    (new QuoteSent('01SECOND', 1))->publish();
    enableCapabilities(Capability::Compliance);
    artisan('capabilities:catch-up')->expectsOutputToContain(__('capabilities.catch_up.done', ['count' => 2]))->assertSuccessful();

    expect(RecordingQuoteListener::$handled)->toBe(['01FIRST', '01SECOND']);
});

it('rebuilds events with enums, dates and nulls from the outbox', function (): void {
    $serializer = new EventSerializer();
    $confirmed = new BookingItemConfirmed('01ITEM', '01BOOK', 'EXP-1', 7, null, null, 'Hotel', 150000, 'COP', CarbonImmutable::parse('2026-11-10 00:00:00'));
    $received = new PaymentReceived('01PAY', '01BOOK', 5000, 'USD', PaymentMethod::cases()[0]);

    expect($serializer->fromPayload(BookingItemConfirmed::class, $serializer->toPayload($confirmed)))->toEqual($confirmed)
        ->and($serializer->fromPayload(PaymentReceived::class, $serializer->toPayload($received)))->toEqual($received);
});
