<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Communications\Enums\NoticeTemplate;
use App\Modules\Communications\Models\ConversationMessage;
use App\Modules\Crm\Actions\RecordConsentAction;
use App\Modules\Crm\Enums\ConsentChannel;
use App\Modules\Crm\Enums\ConsentPurpose;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Models\Payment;
use App\Modules\Portal\Livewire\RequestAccess;
use App\Modules\Portal\Livewire\TripPortal;
use App\Modules\Portal\Services\TripLinks;
use App\Modules\Pricing\Models\MarkupRule;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    RateLimiter::clear('portal-access:127.0.0.1');
});

/** Expediente de familia cuyo titular autorizó el tratamiento de datos (recibe avisos por WhatsApp). */
function portalBooking(bool $confirmed = true): Booking
{
    $booking = familyBooking(agent());
    $customer = Customer::query()->findOrFail($booking->customer_id);
    app(RecordConsentAction::class)->execute($customer, ConsentPurpose::DataProcessing, true, ConsentChannel::WhatsApp, User::query()->findOrFail($customer->owner_id));
    if ($confirmed) {
        app(ConfirmItemAction::class)->execute(hotelOf($booking), 'HCR-77', null, CarbonImmutable::now());
    }

    return $booking->fresh() ?? $booking;
}

function portalUrl(Booking $booking, ?CarbonImmutable $expiresAt = null): string
{
    return app(TripLinks::class)->trip($booking->ulid, $expiresAt ?? CarbonImmutable::now()->addDay());
}

it('sends the magic link by WhatsApp when the booking becomes confirmed', function (): void {
    $booking = portalBooking();

    $notice = ConversationMessage::query()->where('template', NoticeTemplate::TripPortal->value)->sole();

    expect($notice->body)->toContain((string) $booking->number)
        ->and($notice->body)->toContain('my-trip/' . $booking->ulid)
        ->and($notice->body)->toContain('signature=');
});

it('opens My trip only with a valid signed link', function (): void {
    $booking = portalBooking();

    get(portalUrl($booking))->assertOk()->assertSee('Hotel Caribe')->assertSee('HCR-77')->assertSee(__('portal.statement.title'));
    get(route('portal.trip', ['booking' => $booking->ulid]))->assertForbidden();
    get(str_replace($booking->ulid, (string) portalBooking()->ulid, portalUrl($booking)))->assertForbidden();
    get(portalUrl($booking, CarbonImmutable::now()->subMinute()))->assertForbidden();
});

it('pays the balance online reusing the open link', function (): void {
    $booking = portalBooking();

    Livewire::withQueryParams(['expires' => (string) CarbonImmutable::now()->addDay()->timestamp])
        ->test(TripPortal::class, ['booking' => $booking->ulid])
        ->assertSee(__('portal.itinerary.download'))
        ->call('pay')
        ->assertRedirectContains('fake-checkout');
    Livewire::test(TripPortal::class, ['booking' => $booking->ulid])->call('pay');

    $link = Payment::query()->where('method', PaymentMethod::OnlineLink)->sole();
    expect((string) $link->amount()->getAmount())->toBe('550000.00');
});

it('says there is nothing to pay when the balance is covered or in process', function (): void {
    $booking = portalBooking();
    $owner = User::query()->findOrFail($booking->owner_id);
    $account = app(App\Modules\Bookings\Contracts\BookingAccounts::class)->account($booking->ulid);
    app(App\Modules\Payments\Actions\RecordPaymentAction::class)->execute($owner, $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());

    Livewire::test(TripPortal::class, ['booking' => $booking->ulid])
        ->assertSee(__('portal.statement.paid_in_full'))
        ->call('pay')
        ->assertHasErrors('pay');
});

it('downloads the itinerary and vouchers with signed links only', function (): void {
    $booking = portalBooking();
    $links = app(TripLinks::class);
    $expires = CarbonImmutable::now()->addDay();

    get($links->itinerary($booking->ulid, $expires))->assertOk()->assertHeader('content-type', 'application/pdf');
    get($links->voucher($booking->ulid, hotelOf($booking)->ulid, $expires))->assertOk();
    get($links->voucher($booking->ulid, '01JZZZZZZZZZZZZZZZZZZZZZZZ', $expires))->assertNotFound();
    get(route('portal.itinerary', ['booking' => $booking->ulid]))->assertForbidden();

    $unconfirmed = portalBooking(confirmed: false);
    get($links->voucher($unconfirmed->ulid, hotelOf($unconfirmed)->ulid, $expires))->assertNotFound();
});

it('resends the link only when the booking number and holder document match, without revealing it', function (): void {
    $booking = portalBooking(confirmed: false);
    $customer = Customer::query()->findOrFail($booking->customer_id);

    Livewire::test(RequestAccess::class)
        ->call('send')
        ->assertHasErrors(['bookingNumber', 'documentNumber'])
        ->set('bookingNumber', (string) $booking->number)
        ->set('documentNumber', '000000')
        ->call('send')
        ->assertSee(__('portal.access.sent'))
        ->set('bookingNumber', mb_strtolower((string) $booking->number))
        ->set('documentNumber', $customer->document_number)
        ->call('send')
        ->assertSee(__('portal.access.sent'));

    expect(ConversationMessage::query()->where('template', NoticeTemplate::TripPortal->value)->count())->toBe(1);
    get(route('portal.access'))->assertOk()->assertSee(__('portal.access.title'));
});

it('limits access attempts per hour', function (): void {
    config()->set('travel.portal.access_attempts_per_hour', 2);
    $screen = Livewire::test(RequestAccess::class);

    foreach (range(1, 2) as $attempt) {
        $screen->set('bookingNumber', 'EXP-' . $attempt)->set('documentNumber', '123')->call('send')->assertHasNoErrors();
    }

    $screen->set('bookingNumber', 'EXP-3')->set('documentNumber', '123')->call('send')->assertHasErrors('bookingNumber');
});
