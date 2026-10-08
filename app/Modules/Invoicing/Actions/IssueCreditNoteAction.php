<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Data\NoteLine;
use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Invoicing\Models\InvoiceLine;
use App\Modules\Invoicing\Services\NoteWriter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Emite la nota crédito de una solicitud aprobada (una sola vez por solicitud). */
final readonly class IssueCreditNoteAction
{
    public function __construct(private NoteWriter $writer) {}

    public function execute(string $adjustmentUlid, User $approver, CarbonImmutable $now): ?Invoice
    {
        $note = DB::transaction(function () use ($adjustmentUlid, $approver, $now): ?Invoice {
            $adjustment = InvoiceAdjustment::query()->where('ulid', $adjustmentUlid)->lockForUpdate()->firstOrFail();
            if ($adjustment->status !== AdjustmentStatus::Requested) {
                return null;
            }

            $invoice = Invoice::query()->with('lines')->findOrFail($adjustment->invoice_id);
            $sources = $invoice->lines->keyBy('id');
            $lines = [];
            foreach ($adjustment->lines as $credit) {
                /** @var InvoiceLine $source */
                $source = $sources->get($credit['invoice_line_id']);
                $lines[] = new NoteLine($source->description, $source->kind, $credit['amount_minor'], $credit['tax_minor'], $source->product_type, $source->id, $source->booking_item_ulid);
            }

            /** @var non-empty-list<NoteLine> $lines */
            $note = $this->writer->write(InvoiceType::CreditNote, $invoice, $lines, $adjustment->reason, $approver, $now);
            $adjustment->status = AdjustmentStatus::Issued;
            $adjustment->note_invoice_id = $note->id;
            $adjustment->decided_at = $now;
            $adjustment->save();

            return $note;
        });

        if ($note instanceof Invoice) {
            (new InvoiceIssued($note->ulid, InvoiceType::CreditNote, $note->booking_ulid))->publish();
        }

        return $note;
    }
}
