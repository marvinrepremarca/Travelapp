<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Services;

use App\Modules\Invoicing\Enums\AdjustmentStatus;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceAdjustment;
use App\Modules\Invoicing\Models\InvoiceLine;
use Brick\Money\Money;

/** Cuánto se ha acreditado (o está por acreditarse) de cada línea y el valor neto de una factura con sus notas. */
final class InvoiceBalance
{
    /**
     * Valor e IVA ya comprometidos por línea: notas crédito emitidas + solicitudes pendientes de aprobación.
     *
     * @return array<int, array{amount: int, tax: int}> id de la línea → valores menores
     */
    public function committedByLine(Invoice $invoice): array
    {
        $committed = [];
        $issued = InvoiceLine::query()
            ->whereIn('source_line_id', $invoice->lines->pluck('id'))
            ->toBase()
            ->selectRaw('source_line_id, sum(amount_minor) as amount, sum(tax_minor) as tax')
            ->groupBy('source_line_id')
            ->get();
        foreach ($issued as $row) {
            $committed[(int) $row->source_line_id] = ['amount' => (int) $row->amount, 'tax' => (int) $row->tax];
        }

        $pending = InvoiceAdjustment::query()->where('invoice_id', $invoice->id)->where('status', AdjustmentStatus::Requested)->get(['lines']);
        foreach ($pending as $adjustment) {
            foreach ($adjustment->lines as $line) {
                $current = $committed[$line['invoice_line_id']] ?? ['amount' => 0, 'tax' => 0];
                $committed[$line['invoice_line_id']] = ['amount' => $current['amount'] + $line['amount_minor'], 'tax' => $current['tax'] + $line['tax_minor']];
            }
        }

        return $committed;
    }

    /** Factura − notas crédito + notas débito emitidas. */
    public function net(Invoice $invoice): Money
    {
        $totals = Invoice::query()
            ->where('related_invoice_id', $invoice->id)
            ->toBase()
            ->selectRaw('type, sum(total_minor) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return $invoice->money($invoice->total_minor
            - (int) $totals->get(InvoiceType::CreditNote->value, 0)
            + (int) $totals->get(InvoiceType::DebitNote->value, 0));
    }
}
