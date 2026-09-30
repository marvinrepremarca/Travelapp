<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class UserManagementViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function roleNotAssignable(Role $role): self
    {
        return self::make('role_not_assignable', __('identity.errors.role_not_assignable', ['role' => $role->label()]));
    }

    public static function cannotDeactivateSelf(): self
    {
        return self::make('cannot_deactivate_self', __('identity.errors.cannot_deactivate_self'));
    }

    public static function cannotChangeOwnRole(): self
    {
        return self::make('cannot_change_own_role', __('identity.errors.cannot_change_own_role'));
    }

    public static function branchRequired(): self
    {
        return self::make('branch_required', __('identity.errors.branch_required'));
    }

    public function errorCode(): string
    {
        return $this->stableCode;
    }

    private static function make(string $code, string $message): self
    {
        $exception = new self($message);
        $exception->stableCode = $code;

        return $exception;
    }
}
