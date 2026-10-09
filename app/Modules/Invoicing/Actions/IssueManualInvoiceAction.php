<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Customers\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Data\ManualInvoiceLine;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceLine;
use App\Modules\Invoicing\Services\InvoiceNumbering;
use App\Modules\Pricing\Contracts\IncomeTaxes;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Factura emitida a mano a un cliente, sin expediente (ADR-0007): Facturación funciona aunque Reservas esté apagada.
 * Misma regla fiscal que la factura de un expediente: el ingreso propio lleva IVA y el recaudo para terceros no.
 */
final readonly class IssueManualInvoiceAction
{
    public function __construct(
        private InvoiceNumbering $numbering,
        private IncomeTaxes $taxes,
    ) {}

    /** @param list<ManualInvoiceLine> $lines */
    public function execute(User $actor, Customer $customer, string $currency, array $lines, CarbonImmutable $now): Invoice
    {
        $this->assertValid($currency, $lines);
        $priced = array_map(fn(ManualInvoiceLine $line): array => $this->price($line, $now), $lines);
        $sum = static fn(string $field): int => (int) array_sum(array_column($priced, $field));

        $invoice = DB::transaction(function () use ($actor, $customer, $currency, $priced, $sum, $now): Invoice {
            $number = $this->numbering->next(InvoiceType::Invoice);
            $invoice = new Invoice();
            $invoice->forceFill([
                'type' => InvoiceType::Invoice,
                'prefix' => $number['prefix'],
                'sequence' => $number['sequence'],
                'number' => $number['number'],
                'customer_id' => $customer->id,
                'customer_name' => $customer->display_name,
                'customer_document_type' => $customer->document_type,
                'customer_document_number' => $customer->document_number,
                'customer_email' => $customer->email,
                'customer_city' => $customer->city,
                'owner_id' => $actor->id,
                'branch_id' => $actor->branch_id,
                'currency' => $currency,
                'third_party_minor' => $sum('third_party'),
                'own_income_minor' => $sum('own_income'),
                'tax_minor' => $sum('tax_minor'),
                'total_minor' => $sum('amount_minor') + $sum('tax_minor'),
                'e_invoice_status' => EInvoiceStatus::Pending,
                'issued_by' => $actor->id,
                'issued_at' => $now,
            ])->save();

            foreach ($priced as $position => $line) {
                InvoiceLine::query()->create([
                    'invoice_id' => $invoice->id,
                    'position' => $position + 1,
                    'description' => $line['description'],
                    'product_type' => $line['product_type'],
                    'kind' => $line['kind'],
                    'amount_minor' => $line['amount_minor'],
                    'tax_minor' => $line['tax_minor'],
                ]);
            }

            return $invoice;
        });

        (new InvoiceIssued($invoice->ulid, InvoiceType::Invoice, null))->publish();

        return $invoice;
    }

    /** @param list<ManualInvoiceLine> $lines */
    private function assertValid(string $currency, array $lines): void
    {
        if ($lines === []) {
            throw InvoicingRuleViolation::nothingToInvoice();
        }

        foreach ($lines as $line) {
            if (! $line->amount->isPositive() || $line->amount->getCurrency()->getCurrencyCode() !== $currency) {
                throw InvoicingRuleViolation::invalidCharge();
            }
        }
    }

    /** @return array{description: string, product_type: \App\Modules\Shared\Enums\ProductType|null, kind: InvoiceLineKind, amount_minor: int, tax_minor: int, third_party: int, own_income: int} */
    private function price(ManualInvoiceLine $line, CarbonImmutable $now): array
    {
        $amount = $line->amount->getMinorAmount()->toInt();
        $isOwn = $line->kind === InvoiceLineKind::OwnIncome;
        $tax = $isOwn ? $this->taxes->taxOn($line->amount, $line->productType, $now) : Money::zero($line->amount->getCurrency());

        return [
            'description' => $line->description,
            'product_type' => $line->productType,
            'kind' => $line->kind,
            'amount_minor' => $amount,
            'tax_minor' => $tax->getMinorAmount()->toInt(),
            'third_party' => $isOwn ? 0 : $amount,
            'own_income' => $isOwn ? $amount : 0,
        ];
    }
}
