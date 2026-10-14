<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Capabilities\Capabilities;
use App\Modules\Shared\Enums\Permission;

/**
 * Menú principal ordenado por el orden en que se pone en marcha la agencia: cada etapa depende de las anteriores.
 * El número (1.2, 3.4…) indica qué hacer primero. Textos en lang/es/navigation.php.
 */
final readonly class MainMenu
{
    /**
     * Etapas con sus pasos: [clave de texto, ruta, permiso requerido o null].
     *
     * @var array<string, list<array{0: string, 1: string, 2: Permission|null}>>
     */
    private const STAGES = [
        'setup' => [
            ['agency', 'organization.agency', Permission::OrganizationManage],
            ['branches', 'organization.branches.index', Permission::BranchesManage],
            ['users', 'identity.users.index', Permission::UsersView],
            ['roles', 'identity.roles', Permission::RolesManage],
            ['settings', 'organization.settings', Permission::OrganizationManage],
            ['holidays', 'organization.holidays', Permission::OrganizationManage],
            ['bank_accounts', 'finance.bank-accounts', Permission::FinanceAccess],
        ],
        'products' => [
            ['suppliers', 'suppliers.index', Permission::SuppliersView],
            ['rates', 'pricing.rates', Permission::PricingView],
            ['pricing_rules', 'pricing.rules', Permission::PricingManage],
            ['catalog', 'catalog.index', Permission::CatalogView],
            ['simulator', 'pricing.simulator', Permission::PricingView],
        ],
        'sales' => [
            ['customers', 'customers.index', Permission::CustomersView],
            ['leads', 'crm.leads.index', Permission::LeadsView],
            ['whatsapp', 'communications.inbox', Permission::ConversationsView],
            ['quotes', 'quotes.index', Permission::QuotesView],
            ['flights', 'search.flights', Permission::SearchAccess],
            ['hotels', 'search.hotels', Permission::SearchAccess],
        ],
        'operations' => [
            ['bookings', 'bookings.index', Permission::BookingsView],
            ['customer_payments', 'payments.customers', Permission::PaymentsView],
            ['revenue', 'finance.revenue', Permission::FinanceAccess],
            ['payables', 'finance.payables', Permission::FinanceAccess],
            ['cash', 'finance.cash', Permission::CashAccess],
            ['reconciliation', 'finance.reconciliation', Permission::FinanceAccess],
            ['invoicing', 'invoicing.index', Permission::FinanceAccess],
            ['departures', 'operations.departures', Permission::OperationsManage],
            ['operations_resources', 'operations.resources', Permission::OperationsManage],
        ],
        'control' => [
            ['advisor_dashboard', 'reports.advisor', Permission::AdvisorDashboardView],
            ['management_dashboard', 'reports.management', Permission::MarginsView],
            ['finance_dashboard', 'reports.finance', Permission::FinanceAccess],
            ['compliance', 'compliance.index', Permission::ComplianceManage],
            ['data_requests', 'compliance.requests', Permission::ComplianceManage],
            ['tasks', 'workflow.tasks', Permission::TasksView],
            ['approvals', 'workflow.approvals', Permission::ApprovalsView],
            ['audit', 'audit.index', Permission::AuditView],
            ['security', 'identity.security', null],
            ['profitability', 'finance.profitability', Permission::MarginsView],
        ],
    ];

    public function __construct(private Capabilities $capabilities) {}

    /**
     * Etapas visibles para el usuario, numeradas: "2.3" = etapa 2, paso 3. Los pasos sin permiso o de capacidades apagadas se omiten
     * pero conservan su número para que la guía sea la misma para todos.
     *
     * @return list<array{key: string, number: int, steps: list<array{number: string, key: string, route: string}>}>
     */
    public function for(?User $user): array
    {
        $stages = [];
        $stageNumber = 0;
        foreach (self::STAGES as $stageKey => $steps) {
            $stageNumber++;
            $visible = [];
            foreach ($steps as $index => [$key, $route, $permission]) {
                if (! $this->capabilities->allowsRoute($route) || ($permission instanceof Permission && $user?->can($permission->value) !== true)) {
                    continue;
                }

                $visible[] = ['number' => $stageNumber . '.' . ($index + 1), 'key' => $key, 'route' => $route];
            }

            if ($visible !== []) {
                $stages[] = ['key' => $stageKey, 'number' => $stageNumber, 'steps' => $visible];
            }
        }

        return $stages;
    }
}
