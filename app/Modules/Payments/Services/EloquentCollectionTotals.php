<?php

declare(strict_types=1);

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Contracts\CollectionTotals;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\Refund;
use Brick\Money\Money;
use Carbon\CarbonImmutable;

final class EloquentCollectionTotals implements CollectionTotals
{
    public function netCollectedBetween(string $currency, CarbonImmutable $from, CarbonImmutable $until): Money
    {
        $paid = (int) Payment::query()
            ->where('status', PaymentStatus::Approved)
            ->where('currency', $currency)
            ->where('approved_at', '>=', $from)
            ->where('approved_at', '<', $until)
            ->sum('amount_minor');
        $refunded = (int) Refund::query()
            ->where('status', RefundStatus::Paid)
            ->where('currency', $currency)
            ->where('paid_at', '>=', $from)
            ->where('paid_at', '<', $until)
            ->sum('amount_minor');

        return Money::ofMinor($paid - $refunded, $currency);
    }
}
