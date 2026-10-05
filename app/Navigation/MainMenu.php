<?php

declare(strict_types=1);

namespace App\Navigation;

use App\Modules\Identity\Models\User;
use App\Modules\Shared\Enums\Permission;
use Illuminate\Support\Facades\Route;

/**
 * Menú principal ordenado por el orden en que se pone en marcha la agencia: cada etapa depende de las anteriores.
 * El número (1.2, 3.4…) indica qué hacer primero. Textos en lang/es/navigation.php.
 */
final class MainMenu
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
            ['settings', 'organization.settings', Permission::OrganizationManage],
            ['holidays', 'organization.holidays', Permission::OrganizationManage],
        ],
        'products' => [
            ['suppliers', 'suppliers.index', null],
            ['rates', 'pricing.rates', null],
            ['pricing_rules', 'pricing.rules', Permission::PricingManage],
            ['catalog', 'catalog.index', null],
            ['simulator', 'pricing.simulator', null],
        ],
        'sales' => [
            ['customers', 'crm.customers.index', null],
            ['leads', 'crm.leads.index', null],
            ['flights', 'search.flights', null],
            ['hotels', 'search.hotels', null],
            ['quotes', 'quotes.index', null],
        ],
        'operations' => [
            ['bookings', 'bookings.index', null],
        ],
        'control' => [
            ['tasks', 'workflow.tasks', null],
            ['approvals', 'workflow.approvals', null],
            ['audit', 'audit.index', Permission::AuditView],
            ['security', 'identity.security', null],
        ],
    ];

    /**
     * Etapas visibles para el usuario, numeradas: "2.3" = etapa 2, paso 3. Los pasos sin permiso se omiten
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
                if (! Route::has($route) || ($permission instanceof Permission && $user?->can($permission->value) !== true)) {
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
