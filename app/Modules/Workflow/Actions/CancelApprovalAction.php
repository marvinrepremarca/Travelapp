<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Events\ApprovalResolved;
use App\Modules\Workflow\Exceptions\InvalidApproval;
use App\Modules\Workflow\Models\ApprovalRequest;
use Carbon\CarbonImmutable;

/** Solo quien la pidió puede retirar una solicitud pendiente. */
final class CancelApprovalAction
{
    public function execute(ApprovalRequest $approval, User $requester): ApprovalRequest
    {
        if ($approval->owner_id !== $requester->id) {
            throw InvalidApproval::notOwner();
        }

        if ($approval->status->isFinal()) {
            throw InvalidApproval::alreadyResolved($approval->status);
        }

        $approval->status = ApprovalStatus::Cancelled;
        $approval->decided_at = CarbonImmutable::now();
        $approval->save();

        event(new ApprovalResolved($approval->ulid, $approval->type, ApprovalStatus::Cancelled, $approval->subject_type, $approval->subject_id));

        return $approval;
    }
}
