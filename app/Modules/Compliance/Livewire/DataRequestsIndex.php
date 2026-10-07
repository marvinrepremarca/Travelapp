<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Organization\Contracts\HolidayCalendar;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Solicitudes de titulares de datos (Ley 1581) con su plazo en días hábiles. Abiertas primero, por vencimiento. */
#[Layout('components.layouts.backoffice')]
final class DataRequestsIndex extends Component
{
    use AuthorizesCompliance;
    use WithPagination;

    #[Url(except: true)]
    public bool $onlyOpen = true;

    public function mount(): void
    {
        $this->authorizeCompliance();
    }

    public function updatedOnlyOpen(): void
    {
        $this->resetPage();
    }

    public function render(HolidayCalendar $calendar): View
    {
        $requests = DataSubjectRequest::query()
            ->select(['id', 'ulid', 'number', 'type', 'status', 'requester_name', 'received_at', 'due_on', 'handler_id', 'resolved_at'])
            ->with('handler:id,name')
            ->when($this->onlyOpen, static fn($query) => $query->whereIn('status', DataRequestStatus::open()))
            ->orderBy('resolved_at')
            ->orderBy('due_on')
            ->paginate(config()->integer('travel.compliance.page_size'));

        $today = $this->today();

        return view('compliance::livewire.requests-index', [
            'requests' => $requests,
            'today' => $today,
            'alertLimit' => $calendar->addBusinessDays($today, config()->integer('travel.compliance.request_alert_business_days')),
        ])->title(__('compliance.requests.title'))->layoutData(['heading' => __('compliance.requests.title')]);
    }
}
