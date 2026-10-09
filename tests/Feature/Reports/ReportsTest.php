<?php

declare(strict_types=1);

use App\Modules\Bookings\Actions\ConfirmItemAction;
use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Enums\BookingItemStatus;
use App\Modules\Crm\Models\Lead;
use App\Modules\Finance\Actions\OpenCashSessionAction;
use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Payments\Actions\RecordPaymentAction;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Reports\Data\Period;
use App\Modules\Reports\Livewire\AdvisorDashboard;
use App\Modules\Reports\Livewire\FinanceDashboard;
use App\Modules\Reports\Livewire\ManagementDashboard;
use App\Modules\Reports\Queries\AdvisorWorklistQuery;
use App\Modules\Reports\Queries\FinanceSnapshotQuery;
use App\Modules\Reports\Queries\FunnelQuery;
use App\Modules\Reports\Queries\ReceivablesQuery;
use App\Modules\Reports\Queries\SalesQuery;
use App\Modules\Suppliers\Enums\PaymentTerms;
use App\Modules\Suppliers\Models\Supplier;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    Livewire::withoutLazyLoading();
});

function october(): Period
{
    return Period::month('2026-10', CarbonImmutable::now());
}

function reportsOwner(): User
{
    return userWithRole(Role::AgencyOwner);
}

it('computes sales, margin, ticket and growth by sale date in the agency currency', function (): void {
    $first = agent();
    $second = agent();
    familyBooking($first);
    familyBooking($first);
    familyBooking($second);
    $this->travelTo(CarbonImmutable::parse('2026-11-03 10:00:00'));
    familyBooking($second);

    $report = app(SalesQuery::class)->for(reportsOwner(), october());
    $november = app(SalesQuery::class)->for(reportsOwner(), Period::month('2026-11', CarbonImmutable::now()));

    expect((string) $report->total->sale->getAmount())->toBe('1650000.00')
        ->and((string) $report->total->margin()->getAmount())->toBe('150000.00')
        ->and($report->total->marginRate())->toBe('9.1')
        ->and($report->total->bookings)->toBe(3)
        ->and((string) $report->total->averageTicket()?->getAmount())->toBe('550000.00')
        ->and(array_key_first($report->byOwner))->toBe($first->id)
        ->and($report->byProduct)->toHaveKey('hotel')
        ->and($report->dailySaleMinor[1])->toBe(1_650_000_00)
        ->and($november->total->growthAgainst($report->total))->toBe('-66.7')
        ->and($report->total->growthAgainst(App\Modules\Reports\Data\SalesFigures::zero('COP')))->toBeNull();
});

it('counts cancelled services only by their penalties and respects the viewer scope', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    familyBooking(agent());
    $hotel = hotelOf($booking);
    $hotel->status = BookingItemStatus::Cancelled;
    $hotel->penalty_amount_minor = 20_000_00;
    $hotel->save();

    $mine = app(SalesQuery::class)->for($agent, october());

    expect($mine->total->sale->isZero())->toBeTrue()
        ->and((string) $mine->total->penalties->getAmount())->toBe('20000.00')
        ->and($mine->total->bookings)->toBe(1);
});

it('builds the commercial funnel with quote conversion', function (): void {
    $agent = agent();
    Lead::factory()->ownedBy($agent)->create();
    familyBooking($agent);

    $funnel = app(FunnelQuery::class)->for($agent, october(), $agent->id);

    expect([$funnel->leads, $funnel->quotesSent, $funnel->quotesAccepted, $funnel->bookings])->toBe([1, 1, 1, 1])
        ->and($funnel->conversion())->toBe('100.0')
        ->and((new App\Modules\Reports\Data\Funnel(0, 0, 0, 0))->conversion())->toBeNull();
});

it('lists the advisor pending work', function (): void {
    $agent = agent();
    $booking = familyBooking($agent);
    Lead::factory()->ownedBy($agent)->create(['contact_name' => 'Lead pendiente']);
    $worklist = app(AdvisorWorklistQuery::class);

    expect(array_column($worklist->openLeads($agent), 'contactName'))->toBe(['Lead pendiente'])
        ->and(array_column($worklist->upcomingTrips($agent, CarbonImmutable::parse('2026-11-01')), 'ulid'))->toBe([$booking->ulid])
        ->and($worklist->upcomingTrips($agent, CarbonImmutable::parse('2026-10-01')))->toHaveCount(0)
        ->and($worklist->expiringQuotes($agent, CarbonImmutable::now()))->toHaveCount(0);
});

it('ages receivables by the payment deadline before the trip', function (string $today, string $bucket): void {
    $agent = agent();
    $booking = familyBooking($agent);
    app(ConfirmItemAction::class)->execute(hotelOf($booking), 'HCR-1', null, CarbonImmutable::now());
    $paid = familyBooking($agent);
    app(ConfirmItemAction::class)->execute(hotelOf($paid), 'HCR-2', null, CarbonImmutable::now());
    $account = app(BookingAccounts::class)->account($paid->ulid);
    app(RecordPaymentAction::class)->execute($agent, $account, PaymentMethod::Cash, $account->saleTotal, null, null, CarbonImmutable::now());

    $result = app(ReceivablesQuery::class)->for(reportsOwner(), CarbonImmutable::parse($today));

    expect($result['buckets']->count)->toBe(1)
        ->and((string) $result['buckets']->{$bucket}->getAmount())->toBe('550000.00')
        ->and($result['items'][0]->dueDate?->toDateString())->toBe('2026-10-26');
})->with([
    'later' => ['2026-10-01', 'later'],
    'due soon' => ['2026-10-20', 'dueSoon'],
    'overdue' => ['2026-10-27', 'overdue'],
]);

it('summarizes payables, open cash and invoicing', function (): void {
    $supplier = Supplier::factory()->create(['payment_terms' => PaymentTerms::Credit, 'payment_days' => 0]);
    $booking = familyBooking(agent());
    $hotel = hotelOf($booking);
    $hotel->supplier_id = $supplier->id;
    $hotel->save();
    app(ConfirmItemAction::class)->execute($hotel, 'HCR-1', null, CarbonImmutable::now());
    $snapshot = app(FinanceSnapshotQuery::class);

    $payables = $snapshot->payables(CarbonImmutable::parse('2026-11-11'));

    expect((string) $payables->overdue->getAmount())->toBe('500000.00')
        ->and($payables->count)->toBe(1)
        ->and($snapshot->openCash())->toHaveCount(1)
        ->and($snapshot->invoicing(october())['invoice']->isZero())->toBeTrue();
});

it('shows the dashboards by role', function (): void {
    $agent = agent();
    familyBooking($agent);

    actingAs($agent);
    Livewire::withoutLazyLoading()->test(AdvisorDashboard::class)->assertSee(__('reports.advisor.upcoming_trips'))->assertDontSee(__('reports.kpi.margin'));
    actingAs($agent)->get(route('reports.management'))->assertForbidden();
    actingAs($agent)->get(route('reports.finance'))->assertForbidden();

    actingAs(reportsOwner());
    Livewire::withoutLazyLoading()->test(ManagementDashboard::class)
        ->assertSee(__('reports.management.funnel'))
        ->assertSee(app(App\Modules\Shared\Money\MoneyPresenter::class)->format(Money::of('550000', 'COP')))
        ->set('month', '2026-09')
        ->assertSee(__('reports.no_previous'));

    actingAs(financeUser());
    Livewire::withoutLazyLoading()->test(FinanceDashboard::class)->assertSee(__('reports.finance.receivables'))->assertSee(__('reports.finance.cash'))->assertDontSee(__('reports.finance.no_cash'));
});

it('exports CSV within scope, protected against formula injection and audited', function (): void {
    $agent = agent();
    $agent->name = '=HYPERLINK("http://malo")';
    $agent->save();
    familyBooking($agent);

    $response = actingAs(reportsOwner())->get(route('reports.export', ['report' => 'sales_by_owner', 'month' => '2026-10']));

    $response->assertOk();
    $csv = $response->streamedContent();
    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain('550000.00')
        ->and(Activity::query()->where('description', 'report_exported')->count())->toBe(1);

    actingAs(reportsOwner())->get(route('reports.export', ['report' => 'sales_by_branch']))->assertOk();
    actingAs(financeUser())->get(route('reports.export', ['report' => 'receivables']))->assertOk();
    actingAs($agent)->get(route('reports.export', ['report' => 'sales_by_owner']))->assertForbidden();
    actingAs(reportsOwner())->get(route('reports.export', ['report' => 'desconocido']))->assertNotFound();
});

it('falls back to the current month for invalid periods', function (): void {
    expect(Period::month('2026-13', CarbonImmutable::parse('2026-10-15'))->key())->toBe('2026-10')
        ->and(Period::month('2026-02', CarbonImmutable::now())->days())->toBe(28)
        ->and(Period::month('2026-10', CarbonImmutable::now())->previous()->key())->toBe('2026-09');
});

it('labels the export reports', function (): void {
    foreach (App\Modules\Reports\Data\ReportExport::cases() as $case) {
        expect($case->label())->not->toStartWith('reports.');
    }

    expect(app(OpenCashSessionAction::class))->toBeObject();
});

it('draws pie charts with shares and a compact daily chart on each dashboard', function (): void {
    $agent = agent();
    familyBooking($agent);

    actingAs($agent);
    Livewire::withoutLazyLoading()->test(AdvisorDashboard::class)
        ->assertSee(__('reports.pie.my_by_product'))
        ->assertSee(__('reports.pie.quotes'))
        ->assertSee(__('reports.rate', ['rate' => '100.0']))
        ->assertSeeHtml('h-chart')
        ->assertSeeHtml('stroke-dasharray');

    actingAs(reportsOwner());
    Livewire::withoutLazyLoading()->test(ManagementDashboard::class)
        ->assertSee(__('reports.pie.by_product'))
        ->assertSee(__('reports.pie.by_branch'))
        ->set('month', '2026-01')
        ->assertSee(__('reports.pie.empty'))
        ->assertSee(__('reports.chart.no_data'));

    actingAs(financeUser());
    Livewire::withoutLazyLoading()->test(FinanceDashboard::class)
        ->assertSee(__('reports.pie.receivables'))
        ->assertSee(__('reports.pie.payables'));
});
