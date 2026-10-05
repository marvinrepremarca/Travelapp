<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Actions\SetCancellationPolicyAction;
use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Data\CancellationPolicy;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\PayRefundAction;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Actions\RequestRefundAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Livewire\BookingPayments;
use App\Modules\Payments\Models\Refund;
use App\Modules\Payments\Services\PaymentLedger;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Workflow\Actions\ResolveApprovalAction;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Models\ApprovalRequest;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/**
 * Expediente pagado completo, con el hotel confirmado, política 50 % desde 60 días antes y luego cancelado (a 40 días):
 * el cliente debe la penalidad y se le puede devolver el resto.
 */
function cancelledPaidBooking(): Booking
{
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    app(SetCancellationPolicyAction::class)->execute($hotel, new CancellationPolicy(false, [['days_before' => 60, 'rate_basis_points' => 5000]]));
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
    $account = app(BookingAccounts::class)->account($booking->ulid);
    app(RecordPaymentAction::class)->execute(User::query()->findOrFail($booking->owner_id), $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());
    app(ChangeItemStatusAction::class)->execute(hotelOf($booking), BookingItemStatus::Cancelled, 'El cliente desiste', CarbonImmutable::now());

    return $booking;
}

function refundableOf(Booking $booking): Money
{
    return app(PaymentLedger::class)->summary(app(BookingAccounts::class)->account($booking->ulid), CarbonImmutable::now())->refundable();
}

it('allows refunding what was paid minus penalties', function (): void {
    $booking = cancelledPaidBooking();
    $sale = Money::ofMinor(hotelOf($booking)->sale_amount_minor, 'COP');

    expect(refundableOf($booking)->isEqualTo($sale->minus(Money::ofMinor(intdiv($sale->getMinorAmount()->toInt(), 2), 'COP'))))->toBeTrue();
});

it('allows no refund while the services are active and fully paid', function (): void {
    $booking = familyBooking(agent());
    $account = app(BookingAccounts::class)->account($booking->ulid);
    app(RecordPaymentAction::class)->execute(User::query()->findOrFail($booking->owner_id), $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());

    expect(refundableOf($booking)->isZero())->toBeTrue();
});

it('requests a refund through a finance approval and pays it after approval', function (): void {
    $booking = cancelledPaidBooking();
    $amount = refundableOf($booking);
    $requester = User::query()->findOrFail($booking->owner_id);

    $refund = app(RequestRefundAction::class)->execute($requester, app(BookingAccounts::class)->account($booking->ulid), $amount, 'Cancelación con penalidad', CarbonImmutable::now());

    $approval = ApprovalRequest::query()->where('ulid', $refund->approval_ulid)->sole();
    expect($refund->status)->toBe(RefundStatus::Requested)
        ->and($approval->type)->toBe(ApprovalType::Refund)
        ->and(refundableOf($booking)->isZero())->toBeTrue();

    app(ResolveApprovalAction::class)->execute($approval, ApprovalStatus::Approved, userWithRole(Role::Finance));
    expect($refund->fresh()?->status)->toBe(RefundStatus::Approved);

    app(PayRefundAction::class)->execute(userWithRole(Role::Finance), $refund->fresh() ?? $refund, 'TRF-DEV-1', CarbonImmutable::now());
    $summary = app(PaymentLedger::class)->summary(app(BookingAccounts::class)->account($booking->ulid), CarbonImmutable::now());

    expect($refund->fresh()?->status)->toBe(RefundStatus::Paid)
        ->and($summary->balance->isZero())->toBeTrue()
        ->and($summary->refunded->isEqualTo($amount))->toBeTrue();
});

it('frees the amount when finance rejects the refund', function (): void {
    $booking = cancelledPaidBooking();
    $amount = refundableOf($booking);
    $refund = app(RequestRefundAction::class)->execute(User::query()->findOrFail($booking->owner_id), app(BookingAccounts::class)->account($booking->ulid), $amount, 'x', CarbonImmutable::now());

    app(ResolveApprovalAction::class)->execute(ApprovalRequest::query()->where('ulid', $refund->approval_ulid)->sole(), ApprovalStatus::Rejected, userWithRole(Role::Finance), 'Falta el soporte de la cancelación');

    expect($refund->fresh()?->status)->toBe(RefundStatus::Rejected)
        ->and(refundableOf($booking)->isEqualTo($amount))->toBeTrue()
        ->and(fn() => app(PayRefundAction::class)->execute(userWithRole(Role::Finance), $refund->fresh() ?? $refund, 'X', CarbonImmutable::now()))->toThrow(PaymentRuleViolation::class);
});

it('rejects refunds above the refundable amount', function (): void {
    $booking = cancelledPaidBooking();

    app(RequestRefundAction::class)->execute(User::query()->findOrFail($booking->owner_id), app(BookingAccounts::class)->account($booking->ulid), refundableOf($booking)->plus(Money::of('1', 'COP')), 'x', CarbonImmutable::now());
})->throws(PaymentRuleViolation::class);

it('keeps refunds and ignores decisions on other subjects', function (): void {
    $booking = cancelledPaidBooking();
    $refund = app(RequestRefundAction::class)->execute(User::query()->findOrFail($booking->owner_id), app(BookingAccounts::class)->account($booking->ulid), Money::of('1000', 'COP'), 'x', CarbonImmutable::now());

    event(new App\Modules\Workflow\Events\ApprovalResolved('otro', ApprovalType::Discount, ApprovalStatus::Approved, $refund->getMorphClass(), (string) $refund->id));

    expect($refund->fresh()?->status)->toBe(RefundStatus::Requested)
        ->and(fn() => $refund->delete())->toThrow(LogicException::class);
});

it('requests and pays refunds from the payments screen', function (): void {
    $booking = cancelledPaidBooking();
    $owner = User::query()->findOrFail($booking->owner_id);
    actingAs($owner);

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])
        ->assertSee(__('payments.summary.in_favor', ['amount' => app(App\Modules\Shared\Money\MoneyPresenter::class)->format(refundableOf($booking))]))
        ->set('refund.amount', '999999999')
        ->set('refund.reason', 'Cancelación')
        ->call('requestRefund')
        ->assertHasErrors('refund.amount')
        ->set('refund.amount', '1000')
        ->call('requestRefund')
        ->assertHasNoErrors()
        ->assertSee(RefundStatus::Requested->label());

    $refund = Refund::query()->sole();
    app(ResolveApprovalAction::class)->execute(ApprovalRequest::query()->where('ulid', $refund->approval_ulid)->sole(), ApprovalStatus::Approved, userWithRole(Role::Finance));

    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])->call('payRefund', $refund->ulid)->assertForbidden();

    actingAs(userWithRole(Role::Finance));
    Livewire::test(BookingPayments::class, ['booking' => $booking->ulid])
        ->call('payRefund', $refund->ulid)
        ->assertHasErrors('payoutReference')
        ->set('payoutReference', 'TRF-9')
        ->call('payRefund', $refund->ulid)
        ->assertSee(RefundStatus::Paid->label())
        ->call('payRefund', $refund->ulid)
        ->assertHasErrors('payoutReference')
        ->call('payRefund', 'otro')
        ->assertNotFound();
});

it('labels refund statuses', function (): void {
    foreach (RefundStatus::cases() as $status) {
        expect($status->label())->not->toStartWith('payments.');
    }

    expect(RefundStatus::Rejected->isCommitted())->toBeFalse();
});
