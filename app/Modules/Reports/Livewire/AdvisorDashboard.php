<?php

declare(strict_types=1);

namespace App\Modules\Reports\Livewire;

use App\Modules\Reports\Livewire\Concerns\ReportsPeriod;
use App\Modules\Reports\Queries\AdvisorWorklistQuery;
use App\Modules\Reports\Queries\FunnelQuery;
use App\Modules\Reports\Queries\SalesQuery;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Money\MoneyPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/** Mi tablero: mis ventas del mes, mi conversión y mis pendientes (cotizaciones por vencer, leads, próximos viajes). */
#[Layout('components.layouts.backoffice')]
#[Lazy]
final class AdvisorDashboard extends Component
{
    use ReportsPeriod;

    public function render(SalesQuery $sales, FunnelQuery $funnel, AdvisorWorklistQuery $worklist, MoneyPresenter $presenter): View
    {
        $period = $this->period();
        $me = $this->actor();
        $mine = $sales->for($me, $period, $me->id);

        return view('reports::livewire.advisor', [
            'period' => $period,
            'sales' => $mine,
            'canSeeMargins' => $me->can(Permission::MarginsView->value),
            'funnel' => $funnel->for($me, $period, $me->id),
            'quotes' => $worklist->expiringQuotes($me, CarbonImmutable::now()),
            'leads' => $worklist->openLeads($me),
            'trips' => $worklist->upcomingTrips($me, $this->today()),
            'chart' => $this->chartItems($mine->dailySaleMinor, $mine->currency, $presenter),
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('reports.advisor.title'))
            ->layoutData(['heading' => __('reports.advisor.title')]);
    }
}
