<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Bitácoras de auditoría (columna `log_name` de activity_log). */
enum AuditLogName: string
{
    case Organization = 'organization';
    case Identity = 'identity';
    case Security = 'security';

    public function label(): string
    {
        return __("audit.log_names.{$this->value}");
    }
}
