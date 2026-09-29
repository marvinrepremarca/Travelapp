<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

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

    public function label(): string
    {
        return __('identity.permissions.' . $this->value);
    }
}
