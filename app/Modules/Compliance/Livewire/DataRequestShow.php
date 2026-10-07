<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Actions\ResolveDataRequestAction;
use App\Modules\Compliance\Enums\DataRequestStatus;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\DataSubjectRequest;
use App\Modules\Shared\Enums\AuditLogName;
use App\Modules\Shared\Exceptions\BusinessRuleException;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Detalle de una solicitud de titular: datos (enmascarados hasta que se revelan), plazo y respuesta. */
#[Layout('components.layouts.backoffice')]
final class DataRequestShow extends Component
{
    use AuthorizesCompliance;

    #[Locked]
    public string $requestUlid = '';

    public string $status = '';

    public string $response = '';

    public bool $revealed = false;

    public function mount(DataSubjectRequest $request): void
    {
        $this->authorizeCompliance();
        $this->requestUlid = $request->ulid;
        $this->status = DataRequestStatus::Answered->value;
    }

    /** Revelar los datos del titular queda auditado. */
    public function reveal(): void
    {
        $this->authorizeCompliance();
        $request = $this->request();
        activity(AuditLogName::Compliance->value)->performedOn($request)->causedBy($this->actor())->log('personal_data_revealed');
        $this->revealed = true;
    }

    public function resolve(ResolveDataRequestAction $resolve): void
    {
        $this->authorizeCompliance();
        $this->validate([
            'status' => ['required', Rule::enum(DataRequestStatus::class)],
            'response' => ['nullable', 'string', 'max:5000'],
        ], attributes: ['status' => __('compliance.requests.status'), 'response' => __('compliance.requests.response')]);

        try {
            $resolve->execute($this->actor(), $this->request(), DataRequestStatus::from($this->status), $this->response === '' ? null : $this->response);
        } catch (BusinessRuleException $violation) {
            $this->addError('response', $violation->getMessage());

            return;
        }

        session()->flash('status', __('compliance.requests.updated'));
    }

    public function render(): View
    {
        $request = $this->request();

        return view('compliance::livewire.request-show', [
            'request' => $request,
            'today' => $this->today(),
            'statuses' => collect([DataRequestStatus::InProgress, DataRequestStatus::Answered, DataRequestStatus::Rejected])
                ->mapWithKeys(static fn(DataRequestStatus $status): array => [$status->value => $status->label()])->all(),
        ])->title($request->number ?? '')->layoutData(['heading' => __('compliance.requests.show_title', ['number' => $request->number])]);
    }

    private function request(): DataSubjectRequest
    {
        return DataSubjectRequest::query()->where('ulid', $this->requestUlid)->with('handler:id,name')->firstOrFail();
    }
}
