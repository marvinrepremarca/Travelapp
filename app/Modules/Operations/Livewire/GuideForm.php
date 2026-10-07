<?php

declare(strict_types=1);

namespace App\Modules\Operations\Livewire;

use App\Modules\Operations\Actions\SaveGuideAction;
use App\Modules\Operations\Livewire\Concerns\AuthorizesOperations;
use App\Modules\Operations\Models\Guide;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Crear o editar un guía. */
#[Layout('components.layouts.backoffice')]
final class GuideForm extends Component
{
    use AuthorizesOperations;

    #[Locked]
    public ?string $guideUlid = null;

    public string $name = '';

    public string $phone = '';

    public string $languages = '';

    public string $license_number = '';

    public bool $is_active = true;

    public function mount(?Guide $guide = null): void
    {
        $this->authorizeOperations();
        if ($guide?->exists) {
            $this->guideUlid = $guide->ulid;
            $this->fill(['name' => $guide->name, 'phone' => $guide->phone, 'languages' => (string) $guide->languages, 'license_number' => (string) $guide->license_number, 'is_active' => $guide->is_active]);
        }
    }

    public function save(SaveGuideAction $save): void
    {
        $this->authorizeOperations();
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{7,20}$/'],
            'languages' => ['nullable', 'string', 'max:150'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ], attributes: trans('operations.guides.fields'));

        $guide = $save->execute([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'languages' => $validated['languages'] ?: null,
            'license_number' => $validated['license_number'] ?: null,
            'is_active' => (bool) $validated['is_active'],
        ], $this->guideUlid === null ? null : Guide::query()->where('ulid', $this->guideUlid)->firstOrFail());

        session()->flash('status', __('operations.guides.saved', ['name' => $guide->name]));
        $this->redirectRoute('operations.resources', navigate: true);
    }

    public function render(): View
    {
        $title = $this->guideUlid === null ? __('operations.guides.create') : __('operations.guides.edit');

        return view('operations::livewire.guide-form')->title($title)->layoutData(['heading' => $title]);
    }
}
