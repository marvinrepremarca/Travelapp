<?php

declare(strict_types=1);

namespace App\Modules\Reports\Livewire;

use App\Modules\Invoicing\Enums\InvoiceType;
use App\Modules\Reports\Livewire\Concerns\ReportsPeriod;
use App\Modules\Reports\Queries\FinanceSnapshotQuery;
use App\Modules\Reports\Queries\ReceivablesQuery;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Money\MoneyPresenter;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;
use Livewire\Component;

/** Tablero de finanzas: cartera por vencimiento, cuentas por pagar, cajas abiertas y facturación del mes. */
#[Layout('components.layouts.backoffice')]
#[Lazy]
final class FinanceDashboard extends Component
{
    use ReportsPeriod;

    public function mount(): void
    {
        abort_unless($this->actor()->can(Permission::FinanceAccess->value), 403);
    }

    public function render(ReceivablesQuery $receivables, FinanceSnapshotQuery $snapshot, MoneyPresenter $presenter): View
    {
        $period = $this->period();
        $cartera = $receivables->for($this->actor(), $this->today());

        $payables = $snapshot->payables($this->today());

        return view('reports::livewire.finance', [
            'period' => $period,
            'receivables' => $cartera['buckets'],
            'topReceivables' => array_slice($cartera['items'], 0, config()->integer('travel.reports.list_size')),
            'payables' => $payables,
            'cash' => $snapshot->openCash(),
            'invoicing' => $snapshot->invoicing($period),
            'types' => InvoiceType::cases(),
            'receivablesPie' => $this->moneyPie($this->agingSlices($cartera['buckets']), $presenter),
            'payablesPie' => $this->moneyPie($this->agingSlices($payables), $presenter),
            'presenter' => $presenter,
            'timezone' => config()->string('travel.agency.timezone'),
        ])->title(__('reports.finance.title'))
            ->layoutData(['heading' => __('reports.finance.title')]);
    }
}
