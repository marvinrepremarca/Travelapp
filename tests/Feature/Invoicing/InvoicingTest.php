<?php

declare(strict_types=1);

use App\Modules\Bookings\Contracts\BookingAccounts;
use App\Modules\Bookings\Models\Booking;
use App\Modules\Invoicing\Actions\IssueBookingInvoiceAction;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Livewire\InvoicesIndex;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    $this->seed(TaxReferenceSeeder::class);
});

function issueFor(Booking $booking): Invoice
{
    return app(IssueBookingInvoiceAction::class)->execute(financeUser(), $booking->ulid, CarbonImmutable::now());
}

it('invoices the supplier net as third party and the agency income with its VAT', function (): void {
    Event::fake([InvoiceIssued::class]);
    $booking = confirmedFamilyBooking();

    $invoice = issueFor($booking);

    $lines = $invoice->lines()->get();
    expect($invoice->type)->toBe(InvoiceType::Invoice)
        ->and($invoice->number)->toBe('FV-1')
        ->and($invoice->third_party_minor)->toBe(500_000_00)
        ->and($invoice->own_income_minor)->toBe(50_000_00)
        ->and($invoice->tax_minor)->toBe(9_500_00)
        ->and($invoice->total_minor)->toBe(559_500_00)
        ->and($invoice->total()->isEqualTo(app(BookingAccounts::class)->account($booking->ulid)->saleTotal))->toBeTrue()
        ->and($lines->pluck('kind')->all())->toBe([InvoiceLineKind::ThirdParty, InvoiceLineKind::OwnIncome])
        ->and($lines[1]->tax_minor)->toBe(9_500_00)
        ->and($invoice->customer_name)->not->toBe('')
        ->and($invoice->e_invoice_status)->toBe(EInvoiceStatus::Pending);
    Event::assertDispatched(InvoiceIssued::class, static fn(InvoiceIssued $event): bool => $event->invoiceUlid === $invoice->ulid);
});

it('keeps the customer document encrypted at rest', function (): void {
    $invoice = issueFor(confirmedFamilyBooking());

    $raw = DB::table('invoices')->where('id', $invoice->id)->value('customer_document_number');

    expect($raw)->not->toBe($invoice->customer_document_number)
        ->and($invoice->customer_document_number)->not->toBe('');
});

it('marks the invoice as internal with the null e-invoicing provider', function (): void {
    $invoice = issueFor(confirmedFamilyBooking());

    expect($invoice->fresh()?->e_invoice_status)->toBe(EInvoiceStatus::NotApplicable);
});

it('numbers invoices consecutively without gaps', function (): void {
    $first = issueFor(confirmedFamilyBooking());
    try {
        issueFor(confirmedFamilyBooking(paid: false));
    } catch (InvoicingRuleViolation) {
        // Una emisión rechazada no consume número.
    }

    $second = issueFor(confirmedFamilyBooking());

    expect([$first->sequence, $second->sequence])->toBe([1, 2])
        ->and(DB::table('invoice_sequences')->where('type', InvoiceType::Invoice->value)->value('next_number'))->toBe(3);
});

it('refuses bookings that are not confirmed, not fully paid or already invoiced', function (): void {
    $pending = familyBooking(agent());
    $unpaid = confirmedFamilyBooking(paid: false);
    $paid = confirmedFamilyBooking();
    issueFor($paid);

    expect(fn(): \App\Modules\Invoicing\Models\Invoice => issueFor($pending))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.booking_not_confirmed'))
        ->and(fn(): \App\Modules\Invoicing\Models\Invoice => issueFor($unpaid))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.booking_not_paid', ['balance' => app(MoneyPresenter::class)->format(Money::of('559500', 'COP'))]))
        ->and(fn(): \App\Modules\Invoicing\Models\Invoice => issueFor($paid))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.already_invoiced'));
});

it('keeps issued invoices immutable', function (): void {
    $invoice = issueFor(confirmedFamilyBooking());

    expect(fn() => $invoice->forceFill(['total_minor' => 1])->save())->toThrow(LogicException::class)
        ->and(fn() => $invoice->delete())->toThrow(LogicException::class)
        ->and(fn() => $invoice->lines()->firstOrFail()->update(['amount_minor' => 1]))->toThrow(LogicException::class);
});

it('lists bookings ready to invoice and issues from the screen', function (): void {
    $ready = confirmedFamilyBooking();
    $unpaid = confirmedFamilyBooking(paid: false);
    actingAs(financeUser());

    $screen = Livewire::test(InvoicesIndex::class)
        ->assertSee($ready->number)
        ->assertDontSee($unpaid->number)
        ->call('issue', $unpaid->ulid)
        ->assertHasErrors('issue')
        ->call('issue', $ready->ulid);

    $invoice = Invoice::query()->sole();
    $screen->assertRedirect(route('invoicing.show', $invoice));

    Livewire::test(InvoicesIndex::class)
        ->assertDontSee($ready->number)
        ->set('tab', InvoicesIndex::TAB_ISSUED)
        ->assertSee($invoice->number)
        ->set('type', InvoiceType::CreditNote->value)
        ->assertSee(__('invoicing.issued_empty'))
        ->set('type', '')
        ->set('search', $ready->number)
        ->assertSee($invoice->number);

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
        ->assertSee(__('invoicing.line_kind.third_party'))
        ->assertSee(__('invoicing.masked_document', ['last' => mb_substr($invoice->customer_document_number, -4)]))
        ->assertDontSee($invoice->customer_document_number);
});

it('shows invoicing only to finance', function (): void {
    actingAs(agent())->get(route('invoicing.index'))->assertForbidden();
});

it('labels invoicing enums', function (): void {
    foreach ([...InvoiceType::cases(), ...InvoiceLineKind::cases(), ...EInvoiceStatus::cases()] as $case) {
        expect($case->label())->not->toStartWith('invoicing.');
    }

    expect(InvoiceType::CreditNote->prefix())->toBe('NC-');
});
