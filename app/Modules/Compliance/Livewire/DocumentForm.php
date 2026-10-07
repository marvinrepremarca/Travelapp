<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Livewire;

use App\Modules\Compliance\Actions\SaveComplianceDocumentAction;
use App\Modules\Compliance\Data\DocumentData;
use App\Modules\Compliance\Enums\ComplianceDocumentType;
use App\Modules\Compliance\Livewire\Concerns\AuthorizesCompliance;
use App\Modules\Compliance\Models\ComplianceDocument;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Registrar o renovar un documento legal con vigencia. */
#[Layout('components.layouts.backoffice')]
final class DocumentForm extends Component
{
    use AuthorizesCompliance;

    #[Locked]
    public ?string $documentUlid = null;

    public string $type = '';

    public string $number = '';

    public string $issuer = '';

    public string $starts_on = '';

    public string $expires_on = '';

    public string $responsible_id = '';

    public string $notes = '';

    public function mount(?ComplianceDocument $document = null): void
    {
        $this->authorizeCompliance();
        $this->type = ComplianceDocumentType::Rnt->value;
        $this->responsible_id = (string) $this->actor()->id;
        if ($document?->exists) {
            $this->documentUlid = $document->ulid;
            $this->fill([
                'type' => $document->type->value,
                'number' => $document->number,
                'issuer' => (string) $document->issuer,
                'starts_on' => (string) $document->starts_on?->toDateString(),
                'expires_on' => $document->expires_on->toDateString(),
                'responsible_id' => (string) $document->responsible_id,
                'notes' => (string) $document->notes,
            ]);
        }
    }

    public function save(SaveComplianceDocumentAction $save): void
    {
        $this->authorizeCompliance();
        $validated = $this->validate([
            'type' => ['required', Rule::enum(ComplianceDocumentType::class)],
            'number' => ['required', 'string', 'max:60'],
            'issuer' => ['nullable', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'expires_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'responsible_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: trans('compliance.documents.fields'));

        $document = $save->execute($this->actor(), new DocumentData(
            ComplianceDocumentType::from($validated['type']),
            $validated['number'],
            $validated['issuer'] ?: null,
            $validated['starts_on'] ? CarbonImmutable::parse($validated['starts_on']) : null,
            CarbonImmutable::parse($validated['expires_on']),
            (int) $validated['responsible_id'],
            $validated['notes'] ?: null,
        ), $this->documentUlid === null ? null : ComplianceDocument::query()->where('ulid', $this->documentUlid)->firstOrFail());

        session()->flash('status', __('compliance.documents.saved', ['number' => $document->number]));
        $this->redirectRoute('compliance.documents', navigate: true);
    }

    public function render(): View
    {
        $title = $this->documentUlid === null ? __('compliance.documents.create') : __('compliance.documents.edit');

        return view('compliance::livewire.document-form', [
            'types' => collect(ComplianceDocumentType::cases())->mapWithKeys(static fn(ComplianceDocumentType $type): array => [$type->value => $type->label()])->all(),
            'responsibles' => $this->responsibles(),
        ])->title($title)->layoutData(['heading' => $title]);
    }
}
