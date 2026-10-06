<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Listeners;

use App\Modules\Invoicing\Data\EInvoiceDocument;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceLine;
use App\Modules\Invoicing\Services\EInvoicingRegistry;
use App\Modules\Shared\Enums\QueueName;
use Illuminate\Contracts\Queue\ShouldQueue;

/** Envía el documento al proveedor de facturación electrónica en cola (nunca en el request ni en la transacción). */
final class SubmitInvoiceToEInvoicing implements ShouldQueue
{
    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public function __construct(private readonly EInvoicingRegistry $registry)
    {
        $this->tries = config()->integer('travel.invoicing.submit_tries');
        /** @var list<int> $backoff */
        $backoff = config()->array('travel.invoicing.submit_backoff_seconds');
        $this->backoff = $backoff;
    }

    public function viaQueue(): string
    {
        return QueueName::Default->value;
    }

    public function handle(InvoiceIssued $event): void
    {
        $invoice = Invoice::query()->with(['lines', 'related:id,number'])->where('ulid', $event->invoiceUlid)->firstOrFail();
        if (in_array($invoice->e_invoice_status, [EInvoiceStatus::Accepted, EInvoiceStatus::NotApplicable], true)) {
            return;
        }

        $result = $this->registry->active()->submit(new EInvoiceDocument(
            type: $invoice->type,
            number: $invoice->number,
            relatedNumber: $invoice->related?->number,
            issuedAt: $invoice->issued_at,
            customerName: $invoice->customer_name,
            customerDocumentType: $invoice->customer_document_type->value,
            customerDocumentNumber: $invoice->customer_document_number,
            customerEmail: $invoice->customer_email,
            total: $invoice->total(),
            tax: $invoice->money($invoice->tax_minor),
            lines: array_values($invoice->lines->map(static fn(InvoiceLine $line): array => [
                'description' => $line->description,
                'kind' => $line->kind->value,
                'amount' => $invoice->money($line->amount_minor),
                'tax' => $invoice->money($line->tax_minor),
            ])->all()),
        ));

        $invoice->e_invoice_status = $result->status;
        $invoice->e_invoice_reference = $result->reference;
        $invoice->e_invoice_message = $result->message;
        $invoice->save();
    }
}
