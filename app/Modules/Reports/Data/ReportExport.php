<?php

declare(strict_types=1);

namespace App\Modules\Reports\Data;

use App\Modules\Shared\Enums\Permission;

/** Reportes exportables a CSV con el permiso que exige cada uno. */
enum ReportExport: string
{
    case SalesByOwner = 'sales_by_owner';
    case SalesByBranch = 'sales_by_branch';
    case Receivables = 'receivables';

    public function permission(): Permission
    {
        return match ($this) {
            self::SalesByOwner, self::SalesByBranch => Permission::MarginsView,
            self::Receivables => Permission::FinanceAccess,
        };
    }

    public function label(): string
    {
        return __("reports.export.{$this->value}");
    }
}
