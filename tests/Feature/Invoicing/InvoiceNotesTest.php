<?php

declare(strict_types=1);

use App\Modules\Invoicing\Actions\IssueBookingInvoiceAction;
use App\Modules\Invoicing\Actions\IssueDebitNoteAction;
use App\Modules\Invoicing\Actions\RequestCreditNoteAction;
use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Livewire\InvoiceShow;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Invoicing\Services\InvoiceBalance;
use App\Modules\Pricing\Database\Seeders\TaxReferenceSeeder;
use App\Modules\Pricing\Models\MarkupRule;
use App\Modules\Workflow\Actions\ResolveApprovalAction;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Models\ApprovalRequest;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-01 15:00:00'));
    MarkupRule::factory()->percentage(1000)->create();
    $this->seed(TaxReferenceSeeder::class);
});

/** Factura FV de la familia: tercero 500.000 + ingreso propio 50.000 con IVA 9.500. */
function familyInvoice(): Invoice
{
    return app(IssueBookingInvoiceAction::class)->execute(financeUser(), confirmedFamilyBooking()->ulid, CarbonImmutable::now())->load('lines');
}

/** @param  array<int, int>  $amounts */
function requestCredit(Invoice $invoice, array $amounts, string $reason = 'Cancelación parcial'): InvoiceAdjustment
{
    return app(RequestCreditNoteAction::class)->execute(financeUser(), $invoice, $amounts, $reason);
}

function decide(InvoiceAdjustment $adjustment, ApprovalStatus $decision): void
{
    $approval = ApprovalRequest::query()->where('ulid', $adjustment->approval_ulid)->sole();
    app(ResolveApprovalAction::class)->execute($approval, $decision, financeUser(), $decision === ApprovalStatus::Rejected ? 'No procede' : null);
}

it('issues the credit note when finance approves it, crediting VAT in proportion', function (): void {
    $invoice = familyInvoice();
    [$third, $own] = $invoice->lines->all();

    $adjustment = requestCredit($invoice, [$third->id => 100_000_00, $own->id => 10_000_00]);
    decide($adjustment, ApprovalStatus::Approved);

    $note = Invoice::query()->where('type', InvoiceType::CreditNote)->sole();
    expect($adjustment->fresh()?->status)->toBe(AdjustmentStatus::Issued)
        ->and($note->number)->toBe('NC-1')
        ->and($note->related_invoice_id)->toBe($invoice->id)
        ->and($note->third_party_minor)->toBe(100_000_00)
        ->and($note->own_income_minor)->toBe(10_000_00)
        ->and($note->tax_minor)->toBe(1_900_00)
        ->and($note->customer_document_number)->toBe($invoice->customer_document_number)
        ->and((string) app(InvoiceBalance::class)->net($invoice)->getAmount())->toBe('447600.00');
});

it('closes the request without a note when finance rejects it', function (): void {
    $invoice = familyInvoice();
    $adjustment = requestCredit($invoice, [$invoice->lines[0]->id => 1_000_00]);

    decide($adjustment, ApprovalStatus::Rejected);

    expect($adjustment->fresh()?->status)->toBe(AdjustmentStatus::Rejected)
        ->and(Invoice::query()->where('type', InvoiceType::CreditNote)->count())->toBe(0)
        ->and(app(InvoiceBalance::class)->committedByLine($invoice))->toBe([]);
});

it('never credits more than what is left on a line, counting pending requests', function (): void {
    $invoice = familyInvoice();
    $own = $invoice->lines[1];
    requestCredit($invoice, [$own->id => 30_000_00]);

    expect(fn(): \App\Modules\Invoicing\Models\InvoiceAdjustment => requestCredit($invoice, [$own->id => 20_000_01]))->toThrow(InvoicingRuleViolation::class)
        ->and(fn(): \App\Modules\Invoicing\Models\InvoiceAdjustment => requestCredit($invoice, []))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.nothing_to_credit'));

    // El saldo exacto de la línea toma el IVA restante (sin centavos perdidos por redondeo).
    $last = requestCredit($invoice, [$own->id => 20_000_00]);
    expect($last->lines[0]['tax_minor'] + 5_700_00)->toBe($own->tax_minor);
});

it('issues debit notes with VAT only on agency income', function (): void {
    $invoice = familyInvoice();

    $note = app(IssueDebitNoteAction::class)->execute(financeUser(), $invoice, [
        ['description' => 'Cargo por cambio de fecha', 'kind' => InvoiceLineKind::OwnIncome, 'amount' => Money::of('20000', 'COP')],
        ['description' => 'Penalidad del hotel', 'kind' => InvoiceLineKind::ThirdParty, 'amount' => Money::of('100000', 'COP')],
    ], 'Cambio solicitado por el cliente', CarbonImmutable::now());

    expect($note->number)->toBe('ND-1')
        ->and($note->tax_minor)->toBe(3_800_00)
        ->and($note->total_minor)->toBe(123_800_00)
        ->and((string) app(InvoiceBalance::class)->net($invoice)->getAmount())->toBe('683300.00')
        ->and(fn() => app(IssueDebitNoteAction::class)->execute(financeUser(), $note, [['description' => 'x', 'kind' => InvoiceLineKind::OwnIncome, 'amount' => Money::of('1', 'COP')]], 'x', CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.not_an_invoice'))
        ->and(fn() => app(IssueDebitNoteAction::class)->execute(financeUser(), $invoice, [['description' => 'x', 'kind' => InvoiceLineKind::OwnIncome, 'amount' => Money::of('1', 'USD')]], 'x', CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class)
        ->and(fn() => app(IssueDebitNoteAction::class)->execute(financeUser(), $invoice, [], 'x', CarbonImmutable::now()))->toThrow(InvoicingRuleViolation::class);
});

it('refuses credit notes over notes', function (): void {
    $invoice = familyInvoice();
    $debit = app(IssueDebitNoteAction::class)->execute(financeUser(), $invoice, [['description' => 'x', 'kind' => InvoiceLineKind::OwnIncome, 'amount' => Money::of('100', 'COP')]], 'x', CarbonImmutable::now());

    expect(fn(): \App\Modules\Invoicing\Models\InvoiceAdjustment => requestCredit($debit, []))->toThrow(InvoicingRuleViolation::class, __('invoicing.errors.not_an_invoice'));
});

it('requests credit notes and issues debit notes from the invoice screen', function (): void {
    $invoice = familyInvoice();
    $own = $invoice->lines[1];
    actingAs(financeUser());

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
        ->assertSee(__('invoicing.no_notes'))
        ->call('requestCredit')
        ->assertHasErrors('creditReason')
        ->set("credits.{$own->id}", '999999')
        ->set('creditReason', 'Descuento posventa')
        ->call('requestCredit')
        ->assertHasErrors('credits')
        ->set("credits.{$own->id}", '5000')
        ->call('requestCredit')
        ->assertHasNoErrors()
        ->assertSee(__('invoicing.adjustment_status.requested'))
        ->call('issueDebit')
        ->assertHasErrors(['charges.0.description', 'charges.0.amount', 'debitReason'])
        ->call('addCharge')
        ->call('removeCharge', 1)
        ->set('charges.0.description', 'Cambio de fecha')
        ->set('charges.0.amount', '20000')
        ->set('debitReason', 'Cambio')
        ->call('issueDebit')
        ->assertHasNoErrors()
        ->assertRedirect(route('invoicing.show', Invoice::query()->where('type', InvoiceType::DebitNote)->sole()));

    expect(InvoiceAdjustment::query()->sole()->total_minor)->toBe(5_000_00 + 950_00);

    Livewire::test(InvoiceShow::class, ['invoice' => $invoice])
        ->assertSee('ND-1')
        ->assertSee(__('invoicing.credit_notes.remaining', ['amount' => app(App\Modules\Shared\Money\MoneyPresenter::class)->format(Money::of('500000', 'COP'))]));
});

it('labels adjustment statuses', function (): void {
    foreach (AdjustmentStatus::cases() as $status) {
        expect($status->label())->not->toStartWith('invoicing.');
    }
});
