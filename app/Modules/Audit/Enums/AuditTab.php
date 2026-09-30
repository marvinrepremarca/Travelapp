<?php

declare(strict_types=1);

namespace App\Modules\Audit\Enums;

enum AuditTab: string
{
    case Changes = 'changes';
    case Security = 'security';
    case SensitiveAccess = 'sensitive_access';

    public function label(): string
    {
        return __("audit.tabs.{$this->value}");
    }
}
