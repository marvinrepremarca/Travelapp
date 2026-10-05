<?php

declare(strict_types=1);

use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Finance\Data\ProfitFigures;
use App\Modules\Finance\Livewire\ProfitabilityScreen;
use App\Modules\Finance\Services\ProfitabilityCalculator;
use App\Modules\Identity\Models\User;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Suppliers\Enums\CommissionBase;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
});

/** Expediente de familia (neto 500.000, venta 550.000 COP) con el hotel asignado a un proveedor con comisión. */
function bookingWithCommission(User $agent, ?CommissionBase $base = null, int $basisPoints = 1000): Booking
{
    $booking = familyBooking($agent);
    $supplier = Supplier::factory()->create();
    if ($base instanceof CommissionBase) {
        $supplier->commissions()->create(['product_type' => ProductType::Hotel, 'rate_basis_points' => $basisPoints, 'base' => $base, 'valid_from' => '2026-01-01', 'created_by' => $agent->id]);
    }

    $hotel = hotelOf($booking);
    $hotel->supplier_id = $supplier->id;
    $hotel->save();

    return $booking;
}

function octoberReport(User $viewer, ?int $branchId = null): App\Modules\Finance\Data\ProfitabilityReport
{
    return app(ProfitabilityCalculator::class)->report($viewer, CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-11-01'), $branchId);
}

it('computes sale, cost, margin and expected commission per booking', function (?CommissionBase $base, string $commission, string $profit): void {
    $booking = bookingWithCommission(agent(), $base);

    $figures = octoberReport(financeUser())->bookings[0]->figures;

    expect(octoberReport(financeUser())->bookings[0]->ulid)->toBe($booking->ulid)
        ->and((string) $figures->sale->getAmount())->toBe('550000.00')
        ->and((string) $figures->cost->getAmount())->toBe('500000.00')
        ->and((string) $figures->margin()->getAmount())->toBe('50000.00')
        ->and($figures->marginRate())->toBe('9.09')
        ->and((string) $figures->commission->getAmount())->toBe($commission)
        ->and((string) $figures->profit()->getAmount())->toBe($profit);
})->with([
    'gross base on the sale' => [CommissionBase::Gross, '55000.00', '105000.00'],
    'net base on the cost' => [CommissionBase::Net, '50000.00', '100000.00'],
    'no commission agreed' => [null, '0.00', '50000.00'],
]);

it('counts only penalties for cancelled services', function (): void {
    $booking = bookingWithCommission(agent(), CommissionBase::Gross);
    $hotel = hotelOf($booking);
    $hotel->status = BookingItemStatus::Cancelled;
    $hotel->penalty_amount_minor = 12_000_00;
    $hotel->save();

    $figures = octoberReport(financeUser())->totals['COP'];

    expect($figures->sale->isZero())->toBeTrue()
        ->and($figures->commission->isZero())->toBeTrue()
        ->and($figures->marginRate())->toBeNull()
        ->and((string) $figures->profit()->getAmount())->toBe('12000.00');
});

it('totals by advisor and branch within the viewer scope and the period', function (): void {
    $first = agent();
    $second = agent();
    bookingWithCommission($first);
    bookingWithCommission($first);
    $other = bookingWithCommission($second);

    $this->travelTo(CarbonImmutable::parse('2026-11-02 15:00:00'));
    bookingWithCommission($first);

    $report = octoberReport(financeUser());
    expect($report->bookings)->toHaveCount(3)
        ->and((string) $report->byOwner[$first->id]['COP']->margin()->getAmount())->toBe('100000.00')
        ->and((string) $report->byOwner[$second->id]['COP']->margin()->getAmount())->toBe('50000.00')
        ->and((string) $report->byBranch[(int) $second->branch_id]['COP']->sale->getAmount())->toBe('550000.00')
        ->and((string) $report->totals['COP']->sale->getAmount())->toBe('1650000.00')
        ->and(octoberReport(financeUser(), (int) $second->branch_id)->bookings)->toHaveCount(1);

    $manager = branchManager();
    $manager->branch_id = $second->branch_id;
    $manager->save();
    expect(array_map(static fn(\App\Modules\Finance\Data\BookingProfit $row): string => $row->ulid, octoberReport($manager)->bookings))->toBe([$other->ulid]);
});

it('shows the report to whoever sees margins', function (): void {
    $agent = agent();
    $booking = bookingWithCommission($agent, CommissionBase::Gross);
    $money = app(MoneyPresenter::class);
    actingAs(financeUser());

    Livewire::test(ProfitabilityScreen::class)
        ->assertSee($booking->number)
        ->assertSee($agent->name)
        ->assertSee(__('finance.profitability.by_branches'))
        ->assertSee($money->format(Money::of('105000', 'COP')))
        ->set('month', '2026-09')
        ->assertSee(__('finance.profitability.empty', ['period' => 'septiembre 2026']))
        ->set('month', 'no-es-un-mes')
        ->assertSee($booking->number)
        ->set('branch', (string) $agent->branch_id)
        ->assertSee($booking->number);
});

it('forbids the report without margin permission', function (): void {
    actingAs(agent())->get(route('finance.profitability'))->assertForbidden();
});

it('adds profit figures in one currency', function (): void {
    $figures = ProfitFigures::zero('USD')->plus(new ProfitFigures(Money::of(100, 'USD'), Money::of(120, 'USD'), Money::zero('USD'), Money::zero('USD')));

    expect($figures->margin()->isNegative())->toBeTrue()
        ->and($figures->marginRate())->toBe('-20.00')
        ->and($figures->currency())->toBe('USD');
});
