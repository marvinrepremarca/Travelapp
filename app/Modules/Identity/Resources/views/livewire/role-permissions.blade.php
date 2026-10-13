@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    @if (session('status'))<x-ui.alert :tone="Tone::Success" role="status">{{ session('status') }}</x-ui.alert>@endif
    <a class="text-brand underline" href="{{ route('identity.roles') }}">{{ __('identity.role_permissions.back') }}</a>

    <x-ui.card :title="$target->label()">
        <form wire:submit="save" class="flex flex-col gap-md" novalidate>
            <p class="text-caption text-text-subtle">
                {{ trans_choice('identity.role_permissions.users', $usersCount, ['count' => $usersCount]) }}
                @if ($isLocked) · {{ __('identity.role_permissions.locked_hint') }}@endif
            </p>
            @error('granted')<x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>@enderror
            <fieldset class="grid gap-sm md:grid-cols-2 xl:grid-cols-3">
                <legend class="sr-only">{{ __('identity.role_permissions.legend', ['role' => $target->label()]) }}</legend>
                @foreach ($permissions as $permission)
                    @php($isReserved = $permission === $reserved)
                    <label class="flex items-start gap-sm text-body" wire:key="perm-{{ $permission->value }}">
                        <input type="checkbox" class="mt-xs rounded-control border-border" value="{{ $permission->value }}"
                            wire:model="granted" @disabled($isLocked || $isReserved)>
                        <span>
                            {{ $permission->label() }}
                            @if ($isReserved && ! $isLocked)<span class="block text-caption text-text-subtle">{{ __('identity.role_permissions.reserved_hint') }}</span>@endif
                        </span>
                    </label>
                @endforeach
            </fieldset>
            @unless ($isLocked)
                <div class="flex flex-wrap gap-sm">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save">{{ __('identity.role_permissions.save') }}</x-ui.button>
                    <x-ui.button type="button" variant="secondary" wire:click="restoreDefaults"
                        wire:confirm="{{ __('identity.role_permissions.confirm_restore', ['role' => $target->label()]) }}" wire:loading.attr="disabled">{{ __('identity.role_permissions.restore') }}</x-ui.button>
                </div>
            @endunless
        </form>
    </x-ui.card>
</div>
