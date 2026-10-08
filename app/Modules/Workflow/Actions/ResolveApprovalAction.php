<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Actions;

use App\Modules\Identity\Models\User;
use App\Modules\Workflow\Enums\ApprovalStatus;
use App\Modules\Workflow\Events\ApprovalResolved;
use App\Modules\Workflow\Exceptions\InvalidApproval;
use App\Modules\Workflow\Models\ApprovalRequest;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Aprueba o rechaza. Nadie decide su propia solicitud; rechazar exige motivo. */
final class ResolveApprovalAction
{
    public function execute(ApprovalRequest $approval, ApprovalStatus $decision, User $approver, ?string $note = null): ApprovalRequest
    {
        if (! in_array($decision, [ApprovalStatus::Approved, ApprovalStatus::Rejected], true)) {
            throw InvalidApproval::invalidDecision();
        }

        if ($decision === ApprovalStatus::Rejected && trim((string) $note) === '') {
            throw InvalidApproval::noteRequired();
        }

        return DB::transaction(function () use ($approval, $decision, $approver, $note): ApprovalRequest {
            $locked = ApprovalRequest::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            $now = CarbonImmutable::now();

            if ($locked->owner_id === $approver->id) {
                throw InvalidApproval::ownRequest();
            }

            if ($locked->status->isFinal()) {
                throw InvalidApproval::alreadyResolved($locked->status);
            }

            if ($locked->expires_at !== null && $locked->expires_at->lessThanOrEqualTo($now)) {
                throw InvalidApproval::expired();
            }

            if (! $approver->can($locked->type->approverPermission()->value)) {
                throw InvalidApproval::notAllowed();
            }

            $locked->status = $decision;
            $locked->decided_by = $approver->id;
            $locked->decision_note = $note;
            $locked->decided_at = $now;
            $locked->save();

            (new ApprovalResolved($locked->ulid, $locked->type, $decision, $locked->subject_type, $locked->subject_id))->publish();

            return $locked;
        });
    }
}
