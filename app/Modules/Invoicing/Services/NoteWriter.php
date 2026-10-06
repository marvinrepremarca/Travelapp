<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Data\NoteLine;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceLine;
use Carbon\CarbonImmutable;

/**
 * Escribe una nota crédito o débito sobre una factura: copia los datos del cliente de la factura,
 * toma su consecutivo y guarda las líneas. Se usa dentro de la transacción de la Action.
 */
final readonly class NoteWriter
{
    public function __construct(private InvoiceNumbering $numbering) {}

    /** @param  non-empty-list<NoteLine>  $lines */
    public function write(InvoiceType $type, Invoice $invoice, array $lines, string $reason, User $actor, CarbonImmutable $now): Invoice
    {
        $number = $this->numbering->next($type);
        $sum = static fn(callable $pick): int => array_sum(array_map($pick, $lines));

        $note = new Invoice();
        $note->forceFill([
            'type' => $type,
            'prefix' => $number['prefix'],
            'sequence' => $number['sequence'],
            'number' => $number['number'],
            'related_invoice_id' => $invoice->id,
            'booking_ulid' => $invoice->booking_ulid,
            'booking_number' => $invoice->booking_number,
            'customer_id' => $invoice->customer_id,
            'customer_name' => $invoice->customer_name,
            'customer_document_type' => $invoice->customer_document_type,
            'customer_document_number' => $invoice->customer_document_number,
            'customer_email' => $invoice->customer_email,
            'customer_city' => $invoice->customer_city,
            'owner_id' => $invoice->owner_id,
            'branch_id' => $invoice->branch_id,
            'currency' => $invoice->currency,
            'third_party_minor' => $sum(static fn(NoteLine $line): int => $line->kind === InvoiceLineKind::ThirdParty ? $line->amountMinor : 0),
            'own_income_minor' => $sum(static fn(NoteLine $line): int => $line->kind === InvoiceLineKind::OwnIncome ? $line->amountMinor : 0),
            'tax_minor' => $sum(static fn(NoteLine $line): int => $line->taxMinor),
            'total_minor' => $sum(static fn(NoteLine $line): int => $line->amountMinor + $line->taxMinor),
            'reason' => $reason,
            'e_invoice_status' => EInvoiceStatus::Pending,
            'issued_by' => $actor->id,
            'issued_at' => $now,
        ])->save();

        foreach ($lines as $position => $line) {
            InvoiceLine::query()->create([
                'invoice_id' => $note->id,
                'position' => $position + 1,
                'booking_item_ulid' => $line->bookingItemUlid,
                'source_line_id' => $line->sourceLineId,
                'description' => $line->description,
                'product_type' => $line->productType,
                'kind' => $line->kind,
                'amount_minor' => $line->amountMinor,
                'tax_minor' => $line->taxMinor,
            ]);
        }

        return $note;
    }
}
