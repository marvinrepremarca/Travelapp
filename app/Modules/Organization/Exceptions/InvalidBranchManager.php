<?php

declare(strict_types=1);

namespace App\Modules\Organization\Exceptions;

use App\Modules\Shared\Exceptions\BusinessRuleException;

final class InvalidBranchManager extends BusinessRuleException
{
    public static function notEligible(): self
    {
        return new self(__('organization.errors.branch_manager_not_eligible'));
    }

    public function errorCode(): string
    {
        return 'invalid_branch_manager';
    }
}
