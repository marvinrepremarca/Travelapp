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
    case SupplierRecordsManage = 'suppliers.records.manage';
    case TemporaryPasswordsView = 'identity.temporary_passwords.view';
    case PricingManage = 'pricing.manage';
    case CatalogManage = 'catalog.manage';
    case ApprovalsDiscounts = 'workflow.approvals.discounts';
    case ApprovalsRefunds = 'workflow.approvals.refunds';
    case ApprovalsInvoiceVoids = 'workflow.approvals.invoice_voids';
    case ApprovalsPriceChanges = 'workflow.approvals.price_changes';
    case ApprovalsDataExports = 'workflow.approvals.data_exports';
    case OperationsManage = 'operations.manage';
    case ComplianceManage = 'compliance.manage';
    case SuppliersView = 'suppliers.view';
    case CatalogView = 'catalog.view';
    case PricingView = 'pricing.view';
    case CustomersView = 'customers.view';
    case LeadsView = 'crm.leads.view';
    case ConversationsView = 'communications.view';
    case SearchAccess = 'search.access';
    case QuotesView = 'quotes.view';
    case BookingsView = 'bookings.view';
    case PaymentsView = 'payments.view';
    case CashAccess = 'finance.cash.access';
    case AdvisorDashboardView = 'reports.advisor.view';
    case TasksView = 'workflow.tasks.view';
    case ApprovalsView = 'workflow.approvals.view';

    /**
     * Acceso a cada área del menú: sin el permiso la pantalla no aparece ni abre.
     *
     * @return list<self>
     */
    public static function areaAccess(): array
    {
        return [
            self::SuppliersView, self::CatalogView, self::PricingView, self::CustomersView, self::LeadsView,
            self::ConversationsView, self::SearchAccess, self::QuotesView, self::BookingsView, self::PaymentsView,
            self::CashAccess, self::AdvisorDashboardView, self::TasksView, self::ApprovalsView,
        ];
    }

    public function label(): string
    {
        return __('shared.permissions.' . str_replace('.', '_', $this->value));
    }
}
