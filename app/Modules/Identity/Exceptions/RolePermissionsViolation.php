<?php

declare(strict_types=1);

namespace App\Modules\Identity\Exceptions;

use App\Modules\Identity\Enums\Role;
use App\Modules\Shared\Exceptions\BusinessRuleException;

final class RolePermissionsViolation extends BusinessRuleException
{
    private string $stableCode = '';

    public static function roleLocked(Role $role): self
    {
        return self::make('role_locked', __('identity.role_permissions.errors.role_locked', ['role' => $role->label()]));
    }

    public static function permissionReserved(): self
    {
        return self::make('permission_reserved', __('identity.role_permissions.errors.permission_reserved'));
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
