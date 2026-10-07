<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\ComplianceDocument;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Documentos legales (RNT, pólizas, registro mercantil) con su estado de vigencia. */
#[Layout('components.layouts.backoffice')]
final class DocumentsIndex extends Component
{
    use AuthorizesCompliance;
    use WithPagination;

    public function mount(): void
    {
        $this->authorizeCompliance();
    }

    public function render(): View
    {
        return view('compliance::livewire.documents-index', [
            'documents' => ComplianceDocument::query()->with('responsible:id,name')->orderBy('expires_on')->paginate(config()->integer('travel.compliance.page_size')),
            'today' => $this->today(),
            'alertLimit' => $this->today()->addDays(config()->integer('travel.compliance.document_alert_days')),
        ])->title(__('compliance.documents.title'))->layoutData(['heading' => __('compliance.documents.title')]);
    }
}
