<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Contracts\Approvals;
use App\Modules\Workflow\Data\ApprovalRequestData;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Events\ApprovalRequested;
use App\Modules\Workflow\Exceptions\InvalidApproval;
use App\Modules\Workflow\Models\ApprovalRequest;
use Illuminate\Support\Facades\DB;

final class RequestApprovalAction implements Approvals
{
    public function execute(ApprovalRequestData $data, User $requester): ApprovalRequest
    {
        $subjectType = $data->subject->getMorphClass();
        $subjectId = (string) $data->subject->getKey();

        return DB::transaction(function () use ($data, $requester, $subjectType, $subjectId): ApprovalRequest {
            $pending = ApprovalRequest::query()
                ->where('type', $data->type)
                ->where('subject_type', $subjectType)
                ->where('subject_id', $subjectId)
                ->where('status', ApprovalStatus::Pending)
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                throw InvalidApproval::alreadyPending();
            }

            $approval = new ApprovalRequest([
                'type' => $data->type,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'summary' => $data->summary,
                'justification' => $data->justification,
                'context' => $data->context,
                'expires_at' => $data->expiresAt,
            ]);
            $approval->status = ApprovalStatus::Pending;
            $approval->owner_id = $requester->id;
            $approval->branch_id = $requester->branch_id;
            $approval->save();

            (new ApprovalRequested($approval->ulid, $approval->type, $subjectType, $subjectId))->publish();

            return $approval;
        });
    }

    public function request(ApprovalRequestData $data, User $requester): ApprovalRequest
    {
        return $this->execute($data, $requester);
    }
}
