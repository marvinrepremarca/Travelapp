<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Invoicing\Services\InvoiceBalance;
use App\Modules\Shared\Money\MoneyPresenter;
use App\Modules\Workflow\Contracts\Approvals;
use App\Modules\Workflow\Data\ApprovalRequestData;
use App\Modules\Workflow\Enums\ApprovalType;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

/**
 * Solicita una nota crédito total o parcial: por cada línea, un valor que no supere lo que queda por acreditar.
 * El IVA se acredita en proporción; al acreditar el saldo completo de la línea se toma el IVA restante exacto.
 * Queda pendiente de la aprobación de finanzas (Workflow).
 */
final readonly class RequestCreditNoteAction
{
    public function __construct(
        private InvoiceBalance $balance,
        private Approvals $approvals,
        private MoneyPresenter $presenter,
    ) {}

    /** @param  array<int, int>  $amountsByLine  id de la línea → valor menor a acreditar */
    public function execute(User $actor, Invoice $invoice, array $amountsByLine, string $reason): InvoiceAdjustment
    {
        if ($invoice->type !== InvoiceType::Invoice) {
            throw InvoicingRuleViolation::notAnInvoice();
        }

        return DB::transaction(function () use ($actor, $invoice, $amountsByLine, $reason): InvoiceAdjustment {
            $invoice = Invoice::query()->with('lines')->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $committed = $this->balance->committedByLine($invoice);
            $lines = [];
            foreach ($invoice->lines as $line) {
                $amount = $amountsByLine[$line->id] ?? 0;
                if ($amount === 0) {
                    continue;
                }

                $used = $committed[$line->id] ?? ['amount' => 0, 'tax' => 0];
                $remaining = $line->amount_minor - $used['amount'];
                if ($amount < 0 || $amount > $remaining) {
                    throw InvoicingRuleViolation::creditExceedsLine($line->description, $this->presenter->format($invoice->money(max(0, $remaining))));
                }

                $tax = $amount === $remaining
                    ? $line->tax_minor - $used['tax']
                    : BigDecimal::of($line->tax_minor)->multipliedBy($amount)->dividedBy($line->amount_minor, 0, RoundingMode::HALF_UP)->toInt();
                $lines[] = ['invoice_line_id' => $line->id, 'amount_minor' => $amount, 'tax_minor' => $tax];
            }

            if ($lines === []) {
                throw InvoicingRuleViolation::nothingToCredit();
            }

            $adjustment = InvoiceAdjustment::query()->create([
                'invoice_id' => $invoice->id,
                'status' => AdjustmentStatus::Requested,
                'reason' => $reason,
                'lines' => $lines,
                'total_minor' => array_sum(array_map(static fn(array $line): int => $line['amount_minor'] + $line['tax_minor'], $lines)),
                'requested_by' => $actor->id,
            ]);

            $total = $invoice->money($adjustment->total_minor);
            $approval = $this->approvals->request(new ApprovalRequestData(
                type: ApprovalType::InvoiceVoid,
                subject: $adjustment,
                summary: __('invoicing.credit_notes.approval_summary', ['amount' => $this->presenter->format($total), 'number' => $invoice->number]),
                justification: $reason,
                context: ['amount' => (string) $total->getAmount(), 'currency' => $invoice->currency, 'invoice' => $invoice->number],
            ), $actor);

            $adjustment->approval_ulid = $approval->ulid;
            $adjustment->save();

            return $adjustment;
        });
    }
}
