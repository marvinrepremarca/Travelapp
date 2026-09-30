@php
    use App\Modules\Organization\Enums\SettingKey;
@endphp
<form wire:submit="save" class="flex max-w-form flex-col gap-lg" novalidate>
    <x-ui.card>
        <div class="flex flex-col gap-md">
            @foreach ($keys as $key)
                @php($field = 'values.'.$key->field())
                <x-ui.field :label="$key->label()" :for="$field" :hint="$key->help()" :error="$errors->first($field)" wire:key="setting-{{ $key->field() }}">
                    @if ($key === SettingKey::AgencyTimezone)
                        <x-ui.select :name="$field" wire:model="{{ $field }}" :options="array_combine($timezones, $timezones)" hint />
                    @elseif ($key === SettingKey::TravelAgentScope)
                        <x-ui.select :name="$field" wire:model="{{ $field }}" :options="$agentScopes" hint />
                    @elseif ($key->type() === $booleanType)
                        <x-ui.select :name="$field" wire:model="{{ $field }}" :options="['1' => __('shared.yes'), '0' => __('shared.no')]" hint />
                    @elseif ($key->type() === $integerType)
                        <x-ui.input :name="$field" type="number" min="1" wire:model="{{ $field }}" hint />
                    @else
                        <x-ui.input :name="$field" wire:model="{{ $field }}" hint />
                    @endif
                </x-ui.field>
            @endforeach
        </div>
    </x-ui.card>

    <div class="flex justify-end">
        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save">{{ __('shared.save') }}</span>
            <span wire:loading wire:target="save">{{ __('shared.saving') }}</span>
        </x-ui.button>
    </div>
</form>
