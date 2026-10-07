<?php

declare(strict_types=1);

namespace App\Modules\Reports\Livewire;

use App\Modules\Identity\Models\User;
use App\Modules\Organization\Models\Branch;
use App\Modules\Reports\Livewire\Concerns\ReportsPeriod;
use App\Modules\Reports\Queries\FunnelQuery;
use App\Modules\Reports\Queries\SalesQuery;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\ProductType;
use App\Modules\Shared\Money\MoneyPresenter;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/** Tablero de gerencia: ventas, margen y ticket del mes contra el anterior, desgloses y embudo comercial. */
#[Layout('components.layouts.backoffice')]
#[Lazy]
final class ManagementDashboard extends Component
{
    use ReportsPeriod;

    public function mount(): void
    {
        abort_unless($this->actor()->can(Permission::MarginsView->value), 403);
    }

    public function render(SalesQuery $sales, FunnelQuery $funnel, MoneyPresenter $presenter): View
    {
        $period = $this->period();
        $current = $sales->for($this->actor(), $period);
        $previous = $sales->for($this->actor(), $period->previous());
        $topOwners = array_slice($current->byOwner, 0, config()->integer('travel.reports.top_size'), true);

        $products = collect(ProductType::cases())->mapWithKeys(static fn(ProductType $type): array => [$type->value => $type->label()])->all();
        $branches = Branch::query()->whereIn('id', array_keys($current->byBranch))->pluck('name', 'id')->all();

        return view('reports::livewire.management', [
            'period' => $period,
            'sales' => $current,
            'previous' => $previous->total,
            'funnel' => $funnel->for($this->actor(), $period),
            'topOwners' => $topOwners,
            'owners' => User::query()->whereIn('id', array_keys($topOwners))->pluck('name', 'id')->all(),
            'branches' => $branches,
            'products' => $products,
            'productPie' => $this->moneyPie($this->salesBy($current->byProduct, $products), $presenter),
            'branchPie' => $this->moneyPie($this->salesBy($current->byBranch, $branches + [0 => __('reports.no_branch')]), $presenter),
            'chart' => $this->chartItems($current->dailySaleMinor, $current->currency, $presenter),
            'presenter' => $presenter,
        ])->title(__('reports.management.title'))
            ->layoutData(['heading' => __('reports.management.title')]);
    }
}
