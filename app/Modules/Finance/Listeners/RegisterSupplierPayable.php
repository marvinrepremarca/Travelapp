<?php

declare(strict_types=1);

namespace App\Modules\Finance\Listeners;

use App\Modules\Bookings\Events\BookingItemConfirmed;
use App\Modules\Finance\Enums\PayableStatus;
use App\Modules\Finance\Models\SupplierPayable;
use App\Modules\Finance\Services\DueDateCalculator;
use App\Modules\Suppliers\Contracts\SupplierDirectory;
use App\Modules\Suppliers\Data\SupplierPaymentTerms;
use Illuminate\Support\Facades\Log;

/**
 * Al confirmarse un servicio con proveedor nace su cuenta por pagar por el neto. Idempotente por servicio.
 * Sin proveedor registrado (operación propia) no hay obligación con terceros.
 */
final readonly class RegisterSupplierPayable
{
    public function __construct(
        private SupplierDirectory $suppliers,
        private DueDateCalculator $dueDates,
    ) {}

    public function handle(BookingItemConfirmed $event): void
    {
        $terms = $event->supplierId === null ? null : $this->suppliers->paymentTermsOf($event->supplierId);
        if (! $terms instanceof SupplierPaymentTerms) {
            Log::info('payable_skipped_without_supplier', ['booking_item' => $event->itemUlid]);

            return;
        }

        $payable = SupplierPayable::query()->firstOrNew(['booking_item_ulid' => $event->itemUlid]);
        if ($payable->exists) {
            return;
        }

        $payable->fill([
            'booking_ulid' => $event->bookingUlid,
            'booking_number' => $event->bookingNumber,
            'supplier_id' => $terms->supplierId,
            'owner_id' => $event->ownerId,
            'branch_id' => $event->branchId,
            'description' => $event->description,
            'amount_minor' => $event->netAmountMinor,
            'currency' => $event->netCurrency,
            'service_date' => $event->serviceDate->toDateString(),
            'due_date' => $this->dueDates->dueDate($terms, $event->serviceDate)->toDateString(),
        ]);
        $payable->status = PayableStatus::Open;
        $payable->save();
    }
}
