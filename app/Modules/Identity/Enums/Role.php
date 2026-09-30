<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

use App\Modules\Organization\Contracts\AppSettings;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\VisibilityScope;

/** Roles internos base (travel-domain/references/roles.md). La agencia puede crear roles propios. */
enum Role: string
{
    case SystemAdmin = 'system_admin';
    case AgencyOwner = 'agency_owner';
    case BranchManager = 'branch_manager';
    case TravelAgent = 'travel_agent';
    case Operations = 'operations';
    case ProductManager = 'product_manager';
    case Finance = 'finance';

    public function label(): string
    {
        return __('identity.roles.' . $this->value);
    }

    /** Alcance por defecto al asignar el rol; el de `travel_agent` es configurable ⚙. */
    public function defaultScope(): VisibilityScope
    {
        return match ($this) {
            self::SystemAdmin, self::AgencyOwner, self::Operations, self::ProductManager, self::Finance => VisibilityScope::All,
            self::BranchManager => VisibilityScope::Branch,
            self::TravelAgent => app(AppSettings::class)->travelAgentScope(),
        };
    }

    /** @return list<Permission> */
    public function permissions(): array
    {
        return match ($this) {
            self::SystemAdmin => [Permission::UsersView, Permission::UsersManage, Permission::RolesManage, Permission::AuditView, Permission::IntegrationsManage],
            self::AgencyOwner => Permission::cases(),
            self::BranchManager => [Permission::UsersView, Permission::MarginsView, Permission::ApprovalsDiscounts],
            self::Finance => [Permission::FinanceAccess, Permission::MarginsView, Permission::ApprovalsRefunds, Permission::ApprovalsInvoiceVoids],
            self::TravelAgent, self::Operations, self::ProductManager => [],
        };
    }

    /** Roles que exigen segundo factor ⚙. */
    public function requiresTwoFactor(): bool
    {
        /** @var list<string> $roles */
        $roles = config()->array('travel.security.two_factor_required_roles');

        return in_array($this->value, $roles, true);
    }
}
