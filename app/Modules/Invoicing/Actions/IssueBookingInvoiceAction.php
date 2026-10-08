<?php

declare(strict_types=1);

namespace App\Modules\Invoicing\Actions;

use App\Modules\Bookings\Contracts\BookingInvoicing;
use App\Modules\Bookings\Data\InvoiceableBooking;
use App\Modules\Bookings\Data\InvoiceableLine;
use App\Modules\Bookings\Enums\BookingStatus;
use App\Modules\Crm\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Modules\Invoicing\Enums\EInvoiceStatus;
use App\Modules\Invoicing\Enums\InvoiceLineKind;
use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Invoicing\Events\InvoiceIssued;
use App\Modules\Invoicing\Exceptions\InvoicingRuleViolation;
use App\Modules\Invoicing\Models\Invoice;
use App\Modules\Invoicing\Models\InvoiceLine;
use App\Modules\Invoicing\Services\InvoiceNumbering;
use App\Modules\Payments\Contracts\BookingCollections;
use App\Modules\Shared\Money\MoneyPresenter;
use Brick\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Emite la factura de un expediente confirmado y pagado en su totalidad. Cada servicio se factura en dos líneas:
 * recaudo para el proveedor (mandato, sin IVA) e ingreso propio de la agencia (con su IVA).
 */
final readonly class IssueBookingInvoiceAction
{
    public function __construct(
        private BookingInvoicing $bookings,
        private BookingCollections $collections,
        private InvoiceNumbering $numbering,
        private MoneyPresenter $presenter,
    ) {}

    public function execute(User $actor, string $bookingUlid, CarbonImmutable $now): Invoice
    {
        $booking = $this->bookings->invoiceable($bookingUlid);
        $this->assertInvoiceable($booking);

        try {
            $invoice = DB::transaction(function () use ($actor, $booking, $now): Invoice {
                $customer = Customer::query()->findOrFail($booking->customerId);
                $number = $this->numbering->next(InvoiceType::Invoice);
                $lines = $this->lines($booking);
                $sum = static fn(string $field): int => (int) array_sum(array_column($lines, $field));

                $invoice = new Invoice();
                $invoice->forceFill([
                    'type' => InvoiceType::Invoice,
                    'prefix' => $number['prefix'],
                    'sequence' => $number['sequence'],
                    'number' => $number['number'],
                    'booking_ulid' => $booking->ulid,
                    'booking_number' => $booking->number,
                    'booking_invoice_key' => $booking->ulid,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->display_name,
                    'customer_document_type' => $customer->document_type,
                    'customer_document_number' => $customer->document_number,
                    'customer_email' => $customer->email,
                    'customer_city' => $customer->city,
                    'owner_id' => $booking->ownerId,
                    'branch_id' => $booking->branchId,
                    'currency' => $booking->currency,
                    'third_party_minor' => $sum('third_party'),
                    'own_income_minor' => $sum('own_income'),
                    'tax_minor' => $sum('tax_minor'),
                    'total_minor' => $sum('amount_minor') + $sum('tax_minor'),
                    'e_invoice_status' => EInvoiceStatus::Pending,
                    'issued_by' => $actor->id,
                    'issued_at' => $now,
                ])->save();

                foreach ($lines as $position => $line) {
                    InvoiceLine::query()->create([
                        'invoice_id' => $invoice->id,
                        'position' => $position + 1,
                        'booking_item_ulid' => $line['item'],
                        'description' => $line['description'],
                        'product_type' => $line['product_type'],
                        'kind' => $line['kind'],
                        'amount_minor' => $line['amount_minor'],
                        'tax_minor' => $line['tax_minor'],
                    ]);
                }

                return $invoice;
            });
        } catch (UniqueConstraintViolationException) {
            throw InvoicingRuleViolation::alreadyInvoiced();
        }

        (new InvoiceIssued($invoice->ulid, InvoiceType::Invoice, $booking->ulid))->publish();

        return $invoice;
    }

    private function assertInvoiceable(InvoiceableBooking $booking): void
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw InvoicingRuleViolation::bookingNotConfirmed();
        }

        if (Invoice::query()->where('booking_invoice_key', $booking->ulid)->exists()) {
            throw InvoicingRuleViolation::alreadyInvoiced();
        }

        if ($booking->lines === [] || $booking->total()->isZero()) {
            throw InvoicingRuleViolation::nothingToInvoice();
        }

        $collected = $this->collections->netCollected([$booking->ulid => $booking->currency])[$booking->ulid] ?? Money::zero($booking->currency);
        $balance = $booking->total()->minus($collected);
        if ($balance->isPositive()) {
            throw InvoicingRuleViolation::bookingNotPaid($this->presenter->format($balance));
        }
    }

    /**
     * Una línea por naturaleza (tercero / propio) y por servicio; las de valor cero no se facturan.
     *
     * @return list<array{item: string, description: string, product_type: \App\Modules\Shared\Enums\ProductType, kind: InvoiceLineKind, amount_minor: int, tax_minor: int, third_party: int, own_income: int}>
     */
    private function lines(InvoiceableBooking $booking): array
    {
        $lines = [];
        foreach ($booking->lines as $line) {
            $lines = [...$lines, ...$this->split($line)];
        }

        return $lines;
    }

    /** @return list<array{item: string, description: string, product_type: \App\Modules\Shared\Enums\ProductType, kind: InvoiceLineKind, amount_minor: int, tax_minor: int, third_party: int, own_income: int}> */
    private function split(InvoiceableLine $line): array
    {
        $parts = [];
        $third = $line->thirdParty->getMinorAmount()->toInt();
        $own = $line->ownIncome->getMinorAmount()->toInt();
        if ($third !== 0) {
            $parts[] = ['item' => $line->itemUlid, 'description' => __('invoicing.lines.third_party', ['service' => $line->description]), 'product_type' => $line->productType, 'kind' => InvoiceLineKind::ThirdParty, 'amount_minor' => $third, 'tax_minor' => 0, 'third_party' => $third, 'own_income' => 0];
        }

        if ($own !== 0 || ! $line->tax->isZero()) {
            $parts[] = ['item' => $line->itemUlid, 'description' => __('invoicing.lines.own_income', ['service' => $line->description]), 'product_type' => $line->productType, 'kind' => InvoiceLineKind::OwnIncome, 'amount_minor' => $own, 'tax_minor' => $line->tax->getMinorAmount()->toInt(), 'third_party' => 0, 'own_income' => $own];
        }

        return $parts;
    }
}
