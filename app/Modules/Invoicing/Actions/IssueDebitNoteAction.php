<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Data\NoteLine;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Services\NoteWriter;
use App\Modules\Pricing\Contracts\IncomeTaxes;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Nota débito: cobros adicionales sobre una factura (penalidades, cambios). Las líneas de ingreso propio
 * causan el IVA vigente; las de recaudo para terceros no.
 */
final readonly class IssueDebitNoteAction
{
    public function __construct(
        private NoteWriter $writer,
        private IncomeTaxes $taxes,
    ) {}

    /** @param  list<array{description: string, kind: InvoiceLineKind, amount: Money}>  $charges */
    public function execute(User $actor, Invoice $invoice, array $charges, string $reason, CarbonImmutable $now): Invoice
    {
        if ($invoice->type !== InvoiceType::Invoice) {
            throw InvoicingRuleViolation::notAnInvoice();
        }

        $lines = [];
        foreach ($charges as $charge) {
            if ($charge['amount']->getCurrency()->getCurrencyCode() !== $invoice->currency || ! $charge['amount']->isPositive()) {
                throw InvoicingRuleViolation::invalidCharge();
            }

            $tax = $charge['kind'] === InvoiceLineKind::OwnIncome ? $this->taxes->taxOn($charge['amount'], null, $now) : $charge['amount']->multipliedBy(0);
            $lines[] = new NoteLine($charge['description'], $charge['kind'], $charge['amount']->getMinorAmount()->toInt(), $tax->getMinorAmount()->toInt());
        }

        if ($lines === []) {
            throw InvoicingRuleViolation::invalidCharge();
        }

        $note = DB::transaction(fn(): Invoice => $this->writer->write(InvoiceType::DebitNote, $invoice, $lines, $reason, $actor, $now));
        InvoiceIssued::dispatch($note->ulid, InvoiceType::DebitNote, $note->booking_ulid);

        return $note;
    }
}
