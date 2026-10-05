<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Finance\Actions\SettleSupplierPayablesAction;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Identity\Enums\Role;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Models\Supplier;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Hotel del expediente de familia (2026-11-10) asignado a un proveedor con las condiciones dadas, y confirmado. */
function confirmedWithSupplier(PaymentTerms $terms, int $days = 30, ?Supplier $supplier = null): BookingItem
{
    $supplier ??= Supplier::factory()->create(['payment_terms' => $terms, 'payment_days' => $days]);
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    $hotel->supplier_id = $supplier->id;
    $hotel->save();

    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());

    return $hotel->fresh() ?? $hotel;
}

it('creates the payable with the net when a supplier service is confirmed', function (PaymentTerms $terms, int $days, string $due): void {
    $hotel = confirmedWithSupplier($terms, $days);

    $payable = SupplierPayable::query()->sole();
    expect($payable->booking_item_ulid)->toBe($hotel->ulid)
        ->and($payable->amount_minor)->toBe($hotel->net_amount_minor)
        ->and($payable->status)->toBe(PayableStatus::Open)
        ->and($payable->due_date->toDateString())->toBe($due);
})->with([
    'credit 30 days after the service' => [PaymentTerms::Credit, 30, '2026-12-10'],
    'prepaid before the service' => [PaymentTerms::Prepaid, 0, '2026-11-03'],
]);

it('creates no payable for services without supplier and only one per service', function (): void {
    $booking = familyBooking(agent());
    app(ConfirmItemAction::class)->execute(hotelOf($booking), 'X', null, CarbonImmutable::now());
    expect(SupplierPayable::query()->count())->toBe(0);

    $hotel = confirmedWithSupplier(PaymentTerms::Credit);
    event(new App\Modules\Bookings\Events\BookingItemConfirmed($hotel->ulid, 'b', 'EXP', 1, null, $hotel->supplier_id, 'x', 1, 'COP', CarbonImmutable::now()));
    expect(SupplierPayable::query()->count())->toBe(1);
});

it('voids open payables when the service is cancelled but keeps paid ones', function (): void {
    $hotel = confirmedWithSupplier(PaymentTerms::Credit);
    app(ChangeItemStatusAction::class)->execute($hotel, BookingItemStatus::Cancelled, 'Desiste', CarbonImmutable::now());

    expect(SupplierPayable::query()->sole()->status)->toBe(PayableStatus::Voided);

    $paid = confirmedWithSupplier(PaymentTerms::Credit);
    app(SettleSupplierPayablesAction::class)->execute(userWithRole(Role::Finance), [SupplierPayable::query()->where('booking_item_ulid', $paid->ulid)->value('ulid')], 'TRF-1', CarbonImmutable::now());
    app(ChangeItemStatusAction::class)->execute($paid, BookingItemStatus::Cancelled, 'Desiste', CarbonImmutable::now());
    expect(SupplierPayable::query()->where('booking_item_ulid', $paid->ulid)->sole()->status)->toBe(PayableStatus::Paid);
});

it('settles several payables of one supplier with one transfer', function (): void {
    $supplier = Supplier::factory()->create(['payment_terms' => PaymentTerms::Credit, 'payment_days' => 15]);
    confirmedWithSupplier(PaymentTerms::Credit, supplier: $supplier);
    confirmedWithSupplier(PaymentTerms::Credit, supplier: $supplier);
    $ulids = SupplierPayable::query()->pluck('ulid')->all();

    $total = app(SettleSupplierPayablesAction::class)->execute(userWithRole(Role::Finance), $ulids, 'TRF-77', CarbonImmutable::now());

    expect($total->getMinorAmount()->toInt())->toBe((int) SupplierPayable::query()->sum('amount_minor'))
        ->and(SupplierPayable::query()->where('status', PayableStatus::Paid)->count())->toBe(2)
        ->and(SupplierPayable::query()->value('payment_reference'))->toBe('TRF-77')
        ->and(fn() => app(SettleSupplierPayablesAction::class)->execute(userWithRole(Role::Finance), $ulids, 'TRF-78', CarbonImmutable::now()))->toThrow(FinanceRuleViolation::class);
});

it('refuses settlements mixing suppliers', function (): void {
    confirmedWithSupplier(PaymentTerms::Credit);
    confirmedWithSupplier(PaymentTerms::Credit);

    app(SettleSupplierPayablesAction::class)->execute(userWithRole(Role::Finance), SupplierPayable::query()->pluck('ulid')->all(), 'TRF', CarbonImmutable::now());
})->throws(FinanceRuleViolation::class);

it('keeps payables as financial records', function (): void {
    confirmedWithSupplier(PaymentTerms::Credit);

    SupplierPayable::query()->sole()->delete();
})->throws(LogicException::class);

it('shows payables only to finance with totals, filters and overdue marks', function (): void {
    $hotel = confirmedWithSupplier(PaymentTerms::Prepaid);
    actingAs(agent())->get(route('finance.payables'))->assertForbidden();
    actingAs(userWithRole(Role::Finance));

    Livewire::test(PayablesIndex::class)
        ->assertSee($hotel->description)
        ->assertSee(trans_choice('finance.payables.items', 1, ['count' => 1]))
        ->set('onlyOverdue', true)
        ->assertSee(__('finance.payables.empty'));

    $this->travelTo(CarbonImmutable::parse('2026-11-05 12:00:00'));
    Livewire::test(PayablesIndex::class)->set('onlyOverdue', true)->assertSee($hotel->description)
        ->set('onlyOverdue', false)->set('status', PayableStatus::Paid->value)->assertSee(__('finance.payables.empty'))
        ->set('status', 'x')->set('supplier', (string) $hotel->supplier_id)->assertSee($hotel->description);
});

it('settles from the screen and reports errors', function (): void {
    $hotel = confirmedWithSupplier(PaymentTerms::Credit);
    $payable = SupplierPayable::query()->sole();
    actingAs(userWithRole(Role::Finance));

    Livewire::test(PayablesIndex::class)
        ->call('settle')
        ->assertHasErrors(['selected', 'paymentReference'])
        ->set('selected', [$payable->ulid])
        ->set('paymentReference', 'TRF-5')
        ->call('settle')
        ->assertHasNoErrors()
        ->assertSee(__('finance.payables.nothing_open'))
        ->set('selected', [$payable->ulid])
        ->set('paymentReference', 'TRF-6')
        ->call('settle')
        ->assertHasErrors('selected');

    expect($hotel->fresh()?->status)->toBe(BookingItemStatus::Confirmed);
});

it('labels payable statuses', function (): void {
    foreach (PayableStatus::cases() as $status) {
        expect($status->label())->not->toStartWith('finance.');
    }
});
