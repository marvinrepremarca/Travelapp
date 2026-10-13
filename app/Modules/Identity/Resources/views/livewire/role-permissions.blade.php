@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <p class="text-text-subtle">{{ __('identity.role_permissions.intro') }}</p>

    @foreach ($roles as $role)
        @php($isLocked = $role === $locked)
        <x-ui.card :title="$role->label()" wire:key="role-{{ $role->value }}">
            <form wire:submit="save('{{ $role->value }}')" class="flex flex-col gap-md" novalidate>
                <p class="text-caption text-text-subtle">
                    {{ trans_choice('identity.role_permissions.users', (int) ($usersByRole[$role->value] ?? 0), ['count' => (int) ($usersByRole[$role->value] ?? 0)]) }}
                    @if ($isLocked) · {{ __('identity.role_permissions.locked_hint') }}@endif
                </p>
                @error("granted.{$role->value}")<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
                <fieldset class="grid gap-sm md:grid-cols-2 xl:grid-cols-3">
                    <legend class="sr-only">{{ __('identity.role_permissions.legend', ['role' => $role->label()]) }}</legend>
                    @foreach ($permissions as $permission)
                        @php($isReserved = $permission === $reserved)
                        <label class="flex items-start gap-sm text-body" wire:key="perm-{{ $role->value }}-{{ $permission->value }}">
                            <input type="checkbox" class="mt-xs rounded-control border-border" value="{{ $permission->value }}"
                                wire:model="granted.{{ $role->value }}" @disabled($isLocked || $isReserved)>
                            <span>
                                {{ $permission->label() }}
                                @if ($isReserved && ! $isLocked)<span class="block text-caption text-text-subtle">{{ __('identity.role_permissions.reserved_hint') }}</span>@endif
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                @unless ($isLocked)
                    <div class="flex flex-wrap gap-sm">
                        <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save('{{ $role->value }}')">{{ __('identity.role_permissions.save') }}</x-ui.button>
                        <x-ui.button type="button" variant="secondary" wire:click="restoreDefaults('{{ $role->value }}')"
                            wire:confirm="{{ __('identity.role_permissions.confirm_restore', ['role' => $role->label()]) }}" wire:loading.attr="disabled">{{ __('identity.role_permissions.restore') }}</x-ui.button>
                    </div>
                @endunless
            </form>
        </x-ui.card>
    @endforeach
</div>
