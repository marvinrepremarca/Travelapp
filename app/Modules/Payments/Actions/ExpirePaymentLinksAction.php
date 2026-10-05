<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Payments\Enums\PaymentMethod;
use App\Modules\Payments\Enums\PaymentStatus;
use App\Modules\Payments\Models\Payment;
use Carbon\CarbonImmutable;

/** Vence los links pendientes cuya vigencia terminó, liberando ese monto del saldo cobrable. */
final class ExpirePaymentLinksAction
{
    public function execute(CarbonImmutable $now): int
    {
        $expired = 0;
        Payment::query()
            ->where('method', PaymentMethod::OnlineLink)
            ->where('status', PaymentStatus::Pending)
            ->where('link_expires_at', '<=', $now)
            ->eachById(static function (Payment $payment) use (&$expired): void {
                $payment->status = PaymentStatus::Expired;
                $payment->save();
                $expired++;
            });

        return $expired;
    }
}
