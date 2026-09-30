<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Events\ApprovalResolved;
use App\Modules\Workflow\Models\ApprovalRequest;
use Carbon\CarbonImmutable;

/** Marca como vencidas las solicitudes pendientes cuyo plazo pasó y avisa al módulo dueño. */
final class ExpireApprovalsAction
{
    private const CHUNK_SIZE = 100;

    public function execute(CarbonImmutable $now): int
    {
        $expired = 0;

        ApprovalRequest::query()
            ->where('status', ApprovalStatus::Pending)
            ->where('expires_at', '<=', $now)
            ->chunkById(self::CHUNK_SIZE, function ($approvals) use ($now, &$expired): void {
                foreach ($approvals as $approval) {
                    $approval->status = ApprovalStatus::Expired;
                    $approval->decided_at = $now;
                    $approval->save();

                    event(new ApprovalResolved($approval->ulid, $approval->type, ApprovalStatus::Expired, $approval->subject_type, $approval->subject_id));
                    $expired++;
                }
            });

        return $expired;
    }
}
