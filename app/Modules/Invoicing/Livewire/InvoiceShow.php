<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Actions\IssueDebitNoteAction;
use App\Modules\Invoicing\Actions\RequestCreditNoteAction;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Invoicing\Services\InvoiceBalance;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Detalle de una factura o nota: cliente, líneas por naturaleza, IVA, estado electrónico y sus notas.
 * Sobre una factura, finanzas solicita notas crédito (con aprobación) y emite notas débito.
 */
#[Layout('components.layouts.backoffice')]
final class InvoiceShow extends Component
{
    private const MAX_CHARGES = 10;

    #[Locked]
    public string $invoiceUlid = '';

    /** @var array<int|string, string> valor a acreditar por id de línea (en unidades de la moneda) */
    public array $credits = [];

    public string $creditReason = '';

    /** @var array<int, array{description: string, kind: string, amount: string}> */
    public array $charges = [];

    public string $debitReason = '';

    public function mount(Invoice $invoice): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
        abort_unless($invoice->isVisibleTo($this->actor()), 404);
        $this->invoiceUlid = $invoice->ulid;
        $this->addCharge();
    }

    public function addCharge(): void
    {
        if (count($this->charges) < self::MAX_CHARGES) {
            $this->charges[] = ['description' => '', 'kind' => InvoiceLineKind::OwnIncome->value, 'amount' => ''];
        }
    }

    public function removeCharge(int $index): void
    {
        unset($this->charges[$index]);
        $this->charges = array_values($this->charges);
    }

    public function requestCredit(RequestCreditNoteAction $request): void
    {
        $this->authorizeFinance();
        $this->validate([
            'credits' => ['array'],
            'credits.*' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'creditReason' => ['required', 'string', 'max:1000'],
        ], attributes: ['creditReason' => __('invoicing.credit_notes.reason'), 'credits.*' => __('invoicing.amount')]);

        $invoice = $this->invoice();
        $amounts = [];
        foreach ($this->credits as $lineId => $value) {
            if ($value !== '' && (int) $lineId > 0) {
                $amounts[(int) $lineId] = Money::of($value, $invoice->currency)->getMinorAmount()->toInt();
            }
        }

        try {
            $request->execute($this->actor(), $invoice, $amounts, $this->creditReason);
        } catch (BusinessRuleException $violation) {
            $this->addError('credits', $violation->getMessage());

            return;
        }

        $this->reset('credits', 'creditReason');
        session()->flash('status', __('invoicing.credit_notes.requested'));
    }

    public function issueDebit(IssueDebitNoteAction $issue): void
    {
        $this->authorizeFinance();
        $this->validate([
            'charges' => ['required', 'array', 'min:1', 'max:' . self::MAX_CHARGES],
            'charges.*.description' => ['required', 'string', 'max:255'],
            'charges.*.kind' => ['required', Rule::enum(InvoiceLineKind::class)],
            'charges.*.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'debitReason' => ['required', 'string', 'max:1000'],
        ], attributes: [
            'charges.*.description' => __('invoicing.debit_notes.description'),
            'charges.*.kind' => __('invoicing.debit_notes.kind'),
            'charges.*.amount' => __('invoicing.debit_notes.amount'),
            'debitReason' => __('invoicing.debit_notes.reason'),
        ]);

        $invoice = $this->invoice();
        $charges = array_values(array_map(static fn(array $charge): array => [
            'description' => $charge['description'],
            'kind' => InvoiceLineKind::from($charge['kind']),
            'amount' => Money::of($charge['amount'], $invoice->currency),
        ], $this->charges));

        try {
            $note = $issue->execute($this->actor(), $invoice, $charges, $this->debitReason, CarbonImmutable::now());
        } catch (BusinessRuleException $violation) {
            $this->addError('charges', $violation->getMessage());

            return;
        }

        session()->flash('status', __('invoicing.debit_notes.issued', ['number' => $note->number]));
        $this->redirectRoute('invoicing.show', $note, navigate: true);
    }

    public function render(MoneyPresenter $presenter, InvoiceBalance $balance): View
    {
        $invoice = Invoice::query()->with(['lines', 'related:id,ulid,number'])->where('ulid', $this->invoiceUlid)->firstOrFail();
        $isInvoice = $invoice->type === InvoiceType::Invoice;
        $title = __('invoicing.show_title', ['type' => $invoice->type->label(), 'number' => $invoice->number]);
        $committed = $isInvoice ? $balance->committedByLine($invoice) : [];

        return view('invoicing::livewire.invoice-show', [
            'invoice' => $invoice,
            'isInvoice' => $isInvoice,
            'remaining' => $invoice->lines->mapWithKeys(static fn($line): array => [$line->id => $line->amount_minor - ($committed[$line->id]['amount'] ?? 0)])->all(),
            'notes' => $isInvoice ? Invoice::query()->where('related_invoice_id', $invoice->id)->orderBy('issued_at')->get(['id', 'ulid', 'type', 'number', 'total_minor', 'currency', 'issued_at', 'e_invoice_status']) : collect(),
            'adjustments' => $isInvoice ? InvoiceAdjustment::query()->where('invoice_id', $invoice->id)->latest('id')->get() : collect(),
            'net' => $isInvoice ? $balance->net($invoice) : null,
            'kinds' => InvoiceLineKind::cases(),
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title($title)->layoutData(['heading' => $title]);
    }

    private function invoice(): Invoice
    {
        return Invoice::query()->where('ulid', $this->invoiceUlid)->firstOrFail();
    }

    private function authorizeFinance(): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
