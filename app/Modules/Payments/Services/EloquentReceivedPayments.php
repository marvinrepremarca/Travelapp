<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\ReceivedPayments;
use App\Modules\Payments\Data\ReceivedPayment;
use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;

final class EloquentReceivedPayments implements ReceivedPayments
{
    public function approvedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): array
    {
        return $this->map($this->banked()
            ->where('currency', $currency)
            ->where('approved_at', '>=', $from)
            ->where('approved_at', '<', $until)
            ->orderBy('approved_at'));
    }

    public function find(array $ulids): array
    {
        return $ulids === [] ? [] : $this->map($this->banked()->whereIn('ulid', $ulids));
    }

    /** @return Builder<Payment> */
    private function banked(): Builder
    {
        return Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->whereIn('method', [PaymentMethod::BankTransfer, PaymentMethod::OnlineLink]);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return list<ReceivedPayment>
     */
    private function map(Builder $query): array
    {
        return array_values($query->get(['ulid', 'booking_ulid', 'method', 'amount_minor', 'currency', 'approved_at', 'reference'])
            ->map(static fn(Payment $payment): ReceivedPayment => new ReceivedPayment(
                $payment->ulid,
                $payment->booking_ulid,
                $payment->method,
                $payment->amount(),
                $payment->approved_at ?? CarbonImmutable::now(),
                $payment->reference,
            ))->all());
    }
}
