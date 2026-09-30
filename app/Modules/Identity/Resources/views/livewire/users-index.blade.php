@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    @error('actions')
        <x-ui.alert :tone="Tone::Danger">{{ $message }}</x-ui.alert>
    @enderror
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row">
            <x-ui.field :label="__('shared.search')" for="search">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" />
            </x-ui.field>
            <x-ui.field :label="__('identity.users.fields.role')" for="role">
                <x-ui.select name="role" wire:model.live="role" :placeholder="__('identity.users.all_roles')"
                    :options="collect($roles)->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all()" />
            </x-ui.field>
        </div>
        @if ($canManage)
            <x-ui.link-button :href="route('identity.users.create')">{{ __('identity.users.create') }}</x-ui.link-button>
        @endif
    </div>

    <div wire:loading.delay wire:target="search,role,gotoPage,nextPage,previousPage">
        <x-ui.skeleton :lines="3" />
    </div>

    <div wire:loading.remove wire:target="search,role,gotoPage,nextPage,previousPage">
        @if ($users->isEmpty())
            <x-ui.empty-state :title="__('identity.users.empty_title')" :description="__('identity.users.empty_description')" />
        @else
            <x-ui.table :caption="__('identity.users.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('identity.users.fields.name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('identity.users.fields.role') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('identity.users.fields.branch_id') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('identity.users.two_factor') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('identity.users.status') }}</th>
                        @if ($canManage)
                            <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                        @endif
                    </tr>
                </x-slot:head>
                @foreach ($users as $user)
                    @php($role = \App\Modules\Identity\Enums\Role::tryFrom((string) $user->roles->first()?->name))
                    <tr wire:key="user-{{ $user->ulid }}">
                        <td class="px-md py-sm">
                            <p class="font-medium">{{ $user->name }}</p>
                            <p class="text-caption text-text-subtle">{{ $user->email }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $role?->label() }}</td>
                        <td class="px-md py-sm">{{ $user->branch?->name ?? __('identity.users.no_branch') }}</td>
                        <td class="px-md py-sm">
                            <x-ui.badge :tone="$user->hasTwoFactorEnabled() ? Tone::Success : Tone::Warning">
                                {{ $user->hasTwoFactorEnabled() ? __('identity.users.two_factor_on') : __('identity.users.two_factor_off') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-md py-sm">
                            <x-ui.badge :tone="$user->is_active ? Tone::Success : Tone::Neutral">
                                {{ $user->is_active ? __('identity.users.active') : __('identity.users.inactive') }}
                            </x-ui.badge>
                        </td>
                        @if ($canManage)
                            <td class="px-md py-sm">
                                <div class="flex flex-wrap justify-end gap-sm">
                                    <a href="{{ route('identity.users.edit', $user) }}" wire:navigate class="text-brand underline">
                                        {{ __('shared.edit') }}<span class="sr-only"> {{ $user->name }}</span>
                                    </a>
                                    <x-ui.button variant="ghost" wire:click="resendInvitation('{{ $user->ulid }}')" wire:loading.attr="disabled">
                                        {{ __('identity.users.resend_invitation') }}<span class="sr-only"> {{ $user->name }}</span>
                                    </x-ui.button>
                                    <x-ui.button variant="ghost" wire:click="toggleActive('{{ $user->ulid }}')" wire:loading.attr="disabled"
                                        wire:confirm="{{ $user->is_active ? __('identity.users.confirm_deactivate', ['name' => $user->name]) : __('identity.users.confirm_activate', ['name' => $user->name]) }}">
                                        {{ $user->is_active ? __('identity.users.deactivate') : __('identity.users.activate') }}<span class="sr-only"> {{ $user->name }}</span>
                                    </x-ui.button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $users->links() }}</div>
        @endif
    </div>
</div>
