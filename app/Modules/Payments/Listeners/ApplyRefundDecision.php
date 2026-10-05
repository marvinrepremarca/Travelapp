<?php

declare(strict_types=1);

namespace App\Modules\Payments\Listeners;

use App\Modules\Payments\Enums\RefundStatus;
use App\Modules\Payments\Models\Refund;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Enums\ApprovalType;
use App\Modules\Workflow\Events\ApprovalResolved;
use Carbon\CarbonImmutable;

/** Aplica la decisión de finanzas (Workflow) al reembolso solicitado. Solo actúa sobre reembolsos aún solicitados. */
final class ApplyRefundDecision
{
    public function handle(ApprovalResolved $event): void
    {
        if ($event->type !== ApprovalType::Refund || $event->subjectType !== (new Refund())->getMorphClass()) {
            return;
        }

        $refund = Refund::query()->whereKey($event->subjectId)->where('status', RefundStatus::Requested)->first();
        if (! $refund instanceof Refund) {
            return;
        }

        $refund->status = $event->status === ApprovalStatus::Approved ? RefundStatus::Approved : RefundStatus::Rejected;
        $refund->decided_at = CarbonImmutable::now();
        $refund->save();
    }
}
