<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Actions\CompleteObligationAction;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\ComplianceObligation;
use App\Modules\Compliance\Queries\ComplianceAlertsQuery;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

/** Panel de cumplimiento: alertas de vencimiento y calendario de obligaciones y documentos. */
#[Layout('components.layouts.backoffice')]
final class ComplianceOverview extends Component
{
    use AuthorizesCompliance;

    public function mount(): void
    {
        $this->authorizeCompliance();
    }

    public function complete(string $obligation, CompleteObligationAction $complete): void
    {
        $this->authorizeCompliance();
        $model = ComplianceObligation::query()->where('ulid', $obligation)->firstOrFail();

        try {
            $next = $complete->execute($this->actor(), $model);
        } catch (BusinessRuleException $violation) {
            $this->addError('calendar', $violation->getMessage());

            return;
        }

        session()->flash('status', $next instanceof \App\Modules\Compliance\Models\ComplianceObligation
            ? __('compliance.obligations.completed_next', ['date' => $next->due_on->isoFormat('LL')])
            : __('compliance.obligations.completed'));
    }

    public function render(ComplianceAlertsQuery $alerts): View
    {
        $today = $this->today();

        return view('compliance::livewire.overview', [
            'counts' => $alerts->counts($today),
            'calendar' => $alerts->calendar($today),
            'today' => $today,
        ])->title(__('compliance.overview.title'))->layoutData(['heading' => __('compliance.overview.title')]);
    }
}
