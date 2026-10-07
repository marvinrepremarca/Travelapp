<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Actions\SaveObligationAction;
use App\Modules\Compliance\Data\ObligationData;
use App\Modules\Compliance\Enums\ObligationRecurrence;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\ComplianceObligation;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Crear o editar una obligación del calendario legal con responsable y periodicidad. */
#[Layout('components.layouts.backoffice')]
final class ObligationForm extends Component
{
    use AuthorizesCompliance;

    #[Locked]
    public ?string $obligationUlid = null;

    public string $title = '';

    public string $description = '';

    public string $due_on = '';

    public string $recurrence = '';

    public string $responsible_id = '';

    public function mount(?ComplianceObligation $obligation = null): void
    {
        $this->authorizeCompliance();
        $this->recurrence = ObligationRecurrence::Once->value;
        $this->responsible_id = (string) $this->actor()->id;
        if ($obligation?->exists) {
            $this->obligationUlid = $obligation->ulid;
            $this->fill([
                'title' => $obligation->title,
                'description' => (string) $obligation->description,
                'due_on' => $obligation->due_on->toDateString(),
                'recurrence' => $obligation->recurrence->value,
                'responsible_id' => (string) $obligation->responsible_id,
            ]);
        }
    }

    public function save(SaveObligationAction $save): void
    {
        $this->authorizeCompliance();
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_on' => ['required', 'date'],
            'recurrence' => ['required', Rule::enum(ObligationRecurrence::class)],
            'responsible_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
        ], attributes: trans('compliance.obligations.fields'));

        $obligation = $save->execute($this->actor(), new ObligationData(
            $validated['title'],
            $validated['description'] ?: null,
            CarbonImmutable::parse($validated['due_on']),
            ObligationRecurrence::from($validated['recurrence']),
            (int) $validated['responsible_id'],
        ), $this->obligationUlid === null ? null : ComplianceObligation::query()->where('ulid', $this->obligationUlid)->firstOrFail());

        session()->flash('status', __('compliance.obligations.saved', ['title' => $obligation->title]));
        $this->redirectRoute('compliance.index', navigate: true);
    }

    public function render(): View
    {
        $heading = $this->obligationUlid === null ? __('compliance.obligations.create') : __('compliance.obligations.edit');

        return view('compliance::livewire.obligation-form', [
            'recurrences' => collect(ObligationRecurrence::cases())->mapWithKeys(static fn(ObligationRecurrence $recurrence): array => [$recurrence->value => $recurrence->label()])->all(),
            'responsibles' => $this->responsibles(),
        ])->title($heading)->layoutData(['heading' => $heading]);
    }
}
