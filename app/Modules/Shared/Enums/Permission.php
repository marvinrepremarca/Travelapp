<?php

declare(strict_types=1);

namespace App\Modules\Shared\Enums;

/** Permisos base con formato `modulo.accion`. Cada módulo agrega los suyos al construirse. */
enum Permission: string
{
    case OrganizationManage = 'organization.manage';
    case BranchesManage = 'organization.branches.manage';
    case UsersView = 'identity.users.view';
    case UsersManage = 'identity.users.manage';
    case RolesManage = 'identity.roles.manage';
    case AuditView = 'audit.view';
    case SensitiveDataView = 'crm.sensitive_data.view';
    case PersonalDataExport = 'crm.personal_data.export';
    case IntegrationsManage = 'integrations.manage';
    case FinanceAccess = 'finance.access';
    case MarginsView = 'pricing.margins.view';
    case CustomersReassign = 'crm.customers.reassign';
    case SuppliersManage = 'suppliers.manage';
    case PricingManage = 'pricing.manage';
    case CatalogManage = 'catalog.manage';
    case ApprovalsDiscounts = 'workflow.approvals.discounts';
    case ApprovalsRefunds = 'workflow.approvals.refunds';
    case ApprovalsInvoiceVoids = 'workflow.approvals.invoice_voids';
    case ApprovalsPriceChanges = 'workflow.approvals.price_changes';
    case ApprovalsDataExports = 'workflow.approvals.data_exports';

    public function label(): string
    {
        return __('shared.permissions.' . str_replace('.', '_', $this->value));
    }
}
