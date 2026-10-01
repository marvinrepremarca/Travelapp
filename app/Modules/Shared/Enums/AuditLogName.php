<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Bitácoras de auditoría (columna `log_name` de activity_log). */
enum AuditLogName: string
{
    case Organization = 'organization';
    case Identity = 'identity';
    case Security = 'security';
    case Workflow = 'workflow';
    case Crm = 'crm';
    case Suppliers = 'suppliers';
    case Pricing = 'pricing';
    case Catalog = 'catalog';
    case Quotes = 'quotes';

    public function label(): string
    {
        return __("audit.log_names.{$this->value}");
    }
}
