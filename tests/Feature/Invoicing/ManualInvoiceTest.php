<?php

declare(strict_types=1);

use App\Modules\Customers\Models\Customer;
use App\Modules\Invoicing\Actions\IssueManualInvoiceAction;
use App\Modules\Invoicing\Data\ManualInvoiceLine;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Livewire\ManualInvoiceForm;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Pricing\Contracts\IncomeTaxes;
use App\Modules\Shared\Enums\Capability;
use App\Modules\Shared\Enums\ProductType;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
});

it('invoices a customer without a booking, taxing only the agency income', function (): void {
    Event::fake([InvoiceIssued::class]);
    $finance = financeUser();
    $customer = Customer::factory()->create();
    $fee = Money::of('100000', 'COP');
    $now = CarbonImmutable::now();

    $invoice = app(IssueManualInvoiceAction::class)->execute($finance, $customer, 'COP', [
        new ManualInvoiceLine('Asesoría de viaje', InvoiceLineKind::OwnIncome, $fee, ProductType::cases()[0]),
        new ManualInvoiceLine('Tiquete recaudado para la aerolínea', InvoiceLineKind::ThirdParty, Money::of('900000', 'COP'), null),
    ], $now);

    $tax = app(IncomeTaxes::class)->taxOn($fee, ProductType::cases()[0], $now);
    expect($invoice->type)->toBe(InvoiceType::Invoice)
        ->and($invoice->booking_ulid)->toBeNull()
        ->and($invoice->customer_id)->toBe($customer->id)
        ->and($invoice->own_income_minor)->toBe(10_000_000)
        ->and($invoice->third_party_minor)->toBe(90_000_000)
        ->and($invoice->tax_minor)->toBe($tax->getMinorAmount()->toInt())
        ->and($invoice->total_minor)->toBe(100_000_000 + $tax->getMinorAmount()->toInt())
        ->and($invoice->lines()->count())->toBe(2);
    Event::assertDispatched(InvoiceIssued::class, static fn(InvoiceIssued $event): bool => $event->invoiceUlid === $invoice->ulid && $event->bookingUlid === null);
});

it('refuses manual invoices without charges or with invalid amounts', function (): void {
    $finance = financeUser();
    $customer = Customer::factory()->create();
    $issue = app(IssueManualInvoiceAction::class);

    expect(fn() => $issue->execute($finance, $customer, 'COP', [], CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class)
        ->and(fn() => $issue->execute($finance, $customer, 'COP', [new ManualInvoiceLine('x', InvoiceLineKind::OwnIncome, Money::zero('COP'), null)], CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class)
        ->and(fn() => $issue->execute($finance, $customer, 'COP', [new ManualInvoiceLine('x', InvoiceLineKind::OwnIncome, Money::of('10', 'USD'), null)], CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class)
        ->and(Invoice::query()->count())->toBe(0);
});

it('issues a manual invoice from the screen even with bookings off', function (): void {
    disableCapabilities(Capability::Bookings, Capability::Portals);
    $finance = financeUser();
    $customer = Customer::factory()->ownedBy($finance)->create(['first_name' => 'Laura', 'last_name' => 'Gómez']);
    actingAs($finance);

    Livewire::test(ManualInvoiceForm::class)
        ->set('customerSearch', $customer->display_name)
        ->assertSee($customer->display_name)
        ->call('chooseCustomer', $customer->ulid)
        ->set('lines.0.description', 'Asesoría')
        ->set('lines.0.amount', '50000')
        ->call('addLine')
        ->set('lines.1.description', 'Hotel recaudado')
        ->set('lines.1.kind', InvoiceLineKind::ThirdParty->value)
        ->set('lines.1.amount', '300000')
        ->call('issue')
        ->assertHasNoErrors()
        ->assertRedirect(route('invoicing.show', Invoice::query()->sole()));

    Livewire::test(InvoiceShow::class, ['invoice' => Invoice::query()->sole()])->assertSee(__('invoicing.manual.without_booking'));
});

it('validates the manual invoice form', function (): void {
    actingAs(financeUser());

    Livewire::test(ManualInvoiceForm::class)
        ->call('issue')
        ->assertHasErrors(['customerUlid', 'lines.0.description', 'lines.0.amount'])
        ->call('removeLine', 0)
        ->assertSet('lines', []);
});

it('lets only finance issue manual invoices', function (): void {
    actingAs(agent())->get(route('invoicing.create'))->assertForbidden();
    actingAs(financeUser())->get(route('invoicing.create'))->assertOk();
});
