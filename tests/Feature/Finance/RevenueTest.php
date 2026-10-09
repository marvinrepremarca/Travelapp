<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ChangeItemStatusAction;
use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\BookingItem;
use App\Modules\Finance\Actions\RecordManualRevenueAction;
use App\Modules\Finance\Data\ManualRevenueData;
use App\Modules\Finance\Enums\PayableSource;
use App\Modules\Finance\Enums\RevenueEntryType;
use App\Modules\Finance\Enums\RevenueSource;
use App\Modules\Finance\Exceptions\FinanceRuleViolation;
use App\Modules\Finance\Livewire\PayablesIndex;
use App\Modules\Finance\Livewire\RevenueScreen;
use App\Modules\Finance\Models\RevenueEntry;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\IntegrationEvents\CatchUpIntegrationEvents;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Hotel del expediente de familia, confirmado. */
function confirmedHotel(): BookingItem
{
    $hotel = hotelOf(familyBooking(agent()));
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());

    return $hotel->fresh() ?? $hotel;
}

it('recognizes revenue at the sale price when a service is confirmed, once', function (): void {
    $hotel = confirmedHotel();

    $entry = RevenueEntry::query()->sole();

    expect($entry->source)->toBe(RevenueSource::BookingItem)
        ->and($entry->entry_type)->toBe(RevenueEntryType::Recognition)
        ->and($entry->amount_minor)->toBe($hotel->sale_amount_minor)
        ->and($entry->currency)->toBe($hotel->booking->sale_currency)
        ->and($entry->customer_name)->toBe($hotel->booking->customer->display_name)
        ->and($entry->recognized_on->toDateString())->toBe('2026-10-01');
});

it('reverses the revenue when the service is cancelled and keeps the original', function (): void {
    $hotel = confirmedHotel();

    app(ChangeItemStatusAction::class)->execute($hotel, BookingItemStatus::Cancelled, 'Desiste', CarbonImmutable::now());
    app(App\Modules\Finance\Listeners\ReverseBookingRevenue::class)->handle(new App\Modules\Bookings\Events\BookingItemCancelled($hotel->ulid));

    expect(RevenueEntry::query()->count())->toBe(2)
        ->and(RevenueEntry::query()->where('entry_type', RevenueEntryType::Reversal)->sole()->amount_minor)->toBe(-$hotel->sale_amount_minor)
        ->and((int) RevenueEntry::query()->sum('amount_minor'))->toBe(0);
});

it('recognizes the sales confirmed while accounting was off once it is on again', function (): void {
    disableCapabilities(Capability::Accounting);
    $hotel = confirmedHotel();
    expect(RevenueEntry::query()->count())->toBe(0);

    config()->set('capabilities.enabled.accounting', true);
    app()->forgetInstance(App\Modules\Shared\Capabilities\Capabilities::class);
    app(CatchUpIntegrationEvents::class)->run();

    expect(RevenueEntry::query()->sole()->amount_minor)->toBe($hotel->sale_amount_minor);
});

it('keeps revenue entries as financial records', function (): void {
    $entry = RevenueEntry::factory()->create();

    expect(fn() => $entry->delete())->toThrow(LogicException::class)
        ->and(fn() => $entry->update(['description' => 'otra']))->toThrow(LogicException::class);
});

it('records manual sales and refuses invalid ones', function (): void {
    $finance = financeUser();
    $today = CarbonImmutable::parse('2026-10-01');

    $entry = app(RecordManualRevenueAction::class)->execute($finance, new ManualRevenueData('Venta externa', 'Ana', Money::of('250000', 'COP'), $today), $today);

    expect($entry->source)->toBe(RevenueSource::Manual)
        ->and($entry->created_by)->toBe($finance->id)
        ->and(fn() => app(RecordManualRevenueAction::class)->execute($finance, new ManualRevenueData('x', null, Money::zero('COP'), $today), $today))
        ->toThrow(FinanceRuleViolation::class, __('finance.errors.revenue_amount_invalid'))
        ->and(fn() => app(RecordManualRevenueAction::class)->execute($finance, new ManualRevenueData('x', null, Money::of('1', 'COP'), $today->addDay()), $today))
        ->toThrow(FinanceRuleViolation::class, __('finance.errors.revenue_date_in_future'));
});

it('shows recognized revenue against collections and invoicing and registers manual sales', function (): void {
    confirmedHotel();
    actingAs(financeUser());

    Livewire::test(RevenueScreen::class)
        ->assertSee(__('finance.revenue.recognized'))
        ->assertSee(__('finance.revenue.collected'))
        ->assertSee(__('finance.revenue.invoiced'))
        ->set('manual.description', 'Venta de agencia aliada')
        ->set('manual.amount', '120000')
        ->set('manual.date', '2026-10-01')
        ->call('record')
        ->assertHasNoErrors()
        ->set('manual.amount', '')
        ->call('record')
        ->assertHasErrors('manual.amount');

    expect(RevenueEntry::query()->where('source', RevenueSource::Manual)->count())->toBe(1);
});

it('hides collections and invoicing figures when those capabilities are off', function (): void {
    disableCapabilities(Capability::Collections, Capability::Invoicing);
    actingAs(financeUser());

    Livewire::test(RevenueScreen::class)
        ->assertSee(__('finance.revenue.recognized'))
        ->assertDontSee(__('finance.revenue.collected'))
        ->assertDontSee(__('finance.revenue.invoiced'));
});

it('lets only finance see revenue', function (): void {
    actingAs(agent())->get(route('finance.revenue'))->assertForbidden();
    actingAs(financeUser())->get(route('finance.revenue'))->assertOk();
});

it('registers supplier payables by hand without a booking', function (): void {
    $supplier = Supplier::factory()->create();
    actingAs(financeUser());

    Livewire::test(PayablesIndex::class)
        ->set('manual.supplier', (string) $supplier->id)
        ->set('manual.description', 'Bus contratado por fuera')
        ->set('manual.amount', '800000')
        ->set('manual.due_date', '2026-10-30')
        ->call('registerManual')
        ->assertHasNoErrors()
        ->assertSee('Bus contratado por fuera')
        ->assertSee(PayableSource::Manual->label());

    $payable = SupplierPayable::query()->sole();
    expect($payable->source)->toBe(PayableSource::Manual)
        ->and($payable->booking_item_ulid)->toBeNull()
        ->and($payable->amount()->isEqualTo(Money::of('800000', 'COP')))->toBeTrue();
});

it('validates manual payables', function (): void {
    actingAs(financeUser());

    Livewire::test(PayablesIndex::class)
        ->call('registerManual')
        ->assertHasErrors(['manual.supplier', 'manual.description', 'manual.amount', 'manual.due_date']);
});
