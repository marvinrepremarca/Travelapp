<?php

declare(strict_types=1);

namespace App\Modules\Payments\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Exceptions\PaymentRuleViolation;
use App\Modules\Payments\Models\Refund;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Finanzas registra que devolvió el dinero (transferencia al cliente) con su comprobante. */
final class PayRefundAction
{
    public function execute(User $actor, Refund $refund, string $payoutReference, CarbonImmutable $now): Refund
    {
        return DB::transaction(static function () use ($actor, $refund, $payoutReference, $now): Refund {
            $refund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($refund->status !== RefundStatus::Approved) {
                throw PaymentRuleViolation::refundNotApproved();
            }

            $refund->status = RefundStatus::Paid;
            $refund->payout_reference = $payoutReference;
            $refund->paid_by = $actor->id;
            $refund->paid_at = $now;
            $refund->save();

            return $refund;
        });
    }
}
