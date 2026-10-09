<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Bookings\Data\BookingAccount;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Aplica a un expediente un anticipo que el cliente pagó antes de tenerlo (ADR-0007). El abono pasa a contar en
 * el saldo de ese expediente; no puede superar lo que falta por cobrar. Queda auditado en el abono.
 */
final readonly class ApplyAdvanceAction
{
    public function __construct(private RecordPaymentAction $payments) {}

    public function execute(Payment $advance, BookingAccount $account, CarbonImmutable $now): Payment
    {
        return DB::transaction(function () use ($advance, $account, $now): Payment {
            // Serializa con los abonos del expediente, igual que un registro nuevo.
            Payment::query()->where('booking_ulid', $account->ulid)->lockForUpdate()->get(['id']);
            $locked = Payment::query()->whereKey($advance->id)->lockForUpdate()->firstOrFail();

            if ($locked->booking_ulid !== null || $locked->status !== PaymentStatus::Approved || $locked->customer_id !== $account->customerId) {
                throw PaymentRuleViolation::advanceNotApplicable();
            }

            $this->payments->assertWithinBalance($account, $locked->amount(), $now);

            $locked->booking_ulid = $account->ulid;
            $locked->save();

            return $locked;
        });
    }
}
