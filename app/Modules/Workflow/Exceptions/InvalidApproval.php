<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;
use App\Modules\Workflow\Enums\ApprovalStatus;

final class InvalidApproval extends BusinessRuleException
{
    public static function alreadyPending(): self
    {
        return new self(__('workflow.errors.approval_already_pending'));
    }

    public static function ownRequest(): self
    {
        return new self(__('workflow.errors.approval_own_request'));
    }

    public static function alreadyResolved(ApprovalStatus $status): self
    {
        return new self(__('workflow.errors.approval_already_resolved', ['status' => $status->label()]));
    }

    public static function expired(): self
    {
        return new self(__('workflow.errors.approval_expired'));
    }

    public static function notAllowed(): self
    {
        return new self(__('workflow.errors.approval_not_allowed'));
    }

    public static function notOwner(): self
    {
        return new self(__('workflow.errors.approval_not_owner'));
    }

    public static function noteRequired(): self
    {
        return new self(__('workflow.errors.approval_note_required'));
    }

    public static function invalidDecision(): self
    {
        return new self(__('workflow.errors.approval_invalid_decision'));
    }

    public function errorCode(): string
    {
        return 'invalid_approval';
    }
}
