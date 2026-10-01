@php
    use App\Modules\Shared\Enums\Permission;

    $items = [
        ['route' => 'dashboard', 'label' => __('shared.dashboard'), 'permission' => null],
        ['route' => 'crm.leads.index', 'label' => __('crm.leads.title'), 'permission' => null],
        ['route' => 'quotes.index', 'label' => __('quotes.title'), 'permission' => null],
        ['route' => 'pricing.simulator', 'label' => __('pricing.simulator.title'), 'permission' => null],
        ['route' => 'pricing.rules', 'label' => __('pricing.rules.title'), 'permission' => Permission::PricingManage],
        ['route' => 'pricing.rates', 'label' => __('pricing.rates.title'), 'permission' => null],
        ['route' => 'catalog.index', 'label' => __('catalog.title'), 'permission' => null],
        ['route' => 'suppliers.index', 'label' => __('suppliers.title'), 'permission' => null],
        ['route' => 'crm.customers.index', 'label' => __('crm.customers.title'), 'permission' => null],
        ['route' => 'workflow.tasks', 'label' => __('workflow.tasks.title'), 'permission' => null],
        ['route' => 'workflow.approvals', 'label' => __('workflow.approvals.title'), 'permission' => null],
        ['route' => 'organization.agency', 'label' => __('organization.agency.title'), 'permission' => Permission::OrganizationManage],
        ['route' => 'organization.branches.index', 'label' => __('organization.branches.title'), 'permission' => Permission::BranchesManage],
        ['route' => 'organization.holidays', 'label' => __('organization.holidays_screen.title'), 'permission' => Permission::OrganizationManage],
        ['route' => 'organization.settings', 'label' => __('organization.settings_screen.title'), 'permission' => Permission::OrganizationManage],
        ['route' => 'identity.users.index', 'label' => __('identity.users.title'), 'permission' => Permission::UsersView],
        ['route' => 'audit.index', 'label' => __('audit.title'), 'permission' => Permission::AuditView],
        ['route' => 'identity.security', 'label' => __('identity.security.title'), 'permission' => null],
    ];
@endphp
<ul class="mt-md flex flex-col gap-xs">
    @foreach ($items as $item)
        @if (Route::has($item['route']) && ($item['permission'] === null || auth()->user()?->can($item['permission']->value)))
            @php($active = request()->routeIs($item['route'].'*'))
            <li wire:key="nav-{{ $item['route'] }}">
                <a href="{{ route($item['route']) }}" wire:navigate
                   @if ($active) aria-current="page" @endif
                   @class([
                       'block rounded-control px-sm py-xs text-body',
                       'bg-brand text-brand-contrast' => $active,
                       'text-text hover:bg-muted' => ! $active,
                   ])>{{ $item['label'] }}</a>
            </li>
        @endif
    @endforeach
</ul>
