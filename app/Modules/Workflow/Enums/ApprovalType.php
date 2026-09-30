<?php

declare(strict_types=1);

namespace App\Modules\Workflow\Enums;

use App\Modules\Shared\Enums\Permission;

/**
 * Acciones sensibles que requieren aprobación (travel-domain/references/roles.md).
 * Cada tipo define el permiso que debe tener quien aprueba; los umbrales ⚙ los aplica el módulo que solicita.
 */
enum ApprovalType: string
{
    case Discount = 'discount';
    case Refund = 'refund';
    case InvoiceVoid = 'invoice_void';
    case ConfirmedPriceChange = 'confirmed_price_change';
    case PersonalDataExport = 'personal_data_export';

    public function approverPermission(): Permission
    {
        return match ($this) {
            self::Discount => Permission::ApprovalsDiscounts,
            self::Refund => Permission::ApprovalsRefunds,
            self::InvoiceVoid => Permission::ApprovalsInvoiceVoids,
            self::ConfirmedPriceChange => Permission::ApprovalsPriceChanges,
            self::PersonalDataExport => Permission::ApprovalsDataExports,
        };
    }

    public function label(): string
    {
        return __("workflow.approval_type.{$this->value}");
    }
}
