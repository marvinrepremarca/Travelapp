<?php

declare(strict_types=1);

namespace App\Modules\Organization\Livewire;

use App\Modules\Organization\Actions\UpdateSettingsAction;
use App\Modules\Organization\Enums\SettingKey;
use App\Modules\Organization\Enums\SettingType;
use App\Modules\Organization\Services\SettingsStore;
use App\Modules\Shared\Enums\Permission;
use App\Modules\Shared\Enums\VisibilityScope;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.backoffice')]
final class SettingsForm extends Component
{
    private const TRUE_VALUE = '1';

    private const FALSE_VALUE = '0';

    /** @var array<string, string|int|bool> Indexado por SettingKey::field() */
    public array $values = [];

    public function mount(SettingsStore $store): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        foreach (SettingKey::cases() as $key) {
            $value = $store->get($key);
            // Los selects del navegador trabajan con texto: los booleanos viajan como "1"/"0".
            $this->values[$key->field()] = is_bool($value) ? ($value ? self::TRUE_VALUE : self::FALSE_VALUE) : $value;
        }
    }

    public function save(UpdateSettingsAction $update): void
    {
        Gate::authorize(Permission::OrganizationManage->value);

        try {
            $update->execute($this->values);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError("values.{$field}", $messages[0]);
            }

            return;
        }

        session()->flash('status', __('organization.settings_screen.saved'));
        $this->redirectRoute('organization.settings', navigate: true);
    }

    public function render(): View
    {
        return view('organization::livewire.settings-form', [
            'keys' => SettingKey::cases(),
            'timezones' => timezone_identifiers_list(),
            'agentScopes' => [
                VisibilityScope::Own->value => VisibilityScope::Own->label(),
                VisibilityScope::Branch->value => VisibilityScope::Branch->label(),
            ],
            'booleanType' => SettingType::Boolean,
            'integerType' => SettingType::Integer,
        ])->title(__('organization.settings_screen.title'))
            ->layoutData(['heading' => __('organization.settings_screen.title')]);
    }
}
