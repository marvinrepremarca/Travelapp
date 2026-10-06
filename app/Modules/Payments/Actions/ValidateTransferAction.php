<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Events\PaymentReceived;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Finanzas confirma (o rechaza) una transferencia pendiente contra el extracto bancario. */
final class ValidateTransferAction
{
    public function execute(User $actor, Payment $payment, bool $approve, ?string $note, CarbonImmutable $now): Payment
    {
        return DB::transaction(static function () use ($actor, $payment, $approve, $note, $now): Payment {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($payment->method !== PaymentMethod::BankTransfer || $payment->status !== PaymentStatus::Pending) {
                throw PaymentRuleViolation::notPending();
            }

            $payment->status = $approve ? PaymentStatus::Approved : PaymentStatus::Rejected;
            $payment->validated_by = $actor->id;
            $payment->approved_at = $approve ? $now : null;
            $payment->note = $note ?? $payment->note;
            $payment->save();

            if ($approve) {
                event(PaymentReceived::of($payment));
            }

            return $payment;
        });
    }
}
