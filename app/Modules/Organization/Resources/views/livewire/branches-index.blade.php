@php
    use App\Modules\Shared\Enums\Tone;
@endphp
<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row">
            <x-ui.field :label="__('shared.search')" for="search">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" />
            </x-ui.field>
            <x-ui.field :label="__('organization.branches.status_filter')" for="status">
                <x-ui.select name="status" wire:model.live="status"
                    :options="collect($filters)->mapWithKeys(fn ($filter) => [$filter->value => $filter->label()])->all()" />
            </x-ui.field>
        </div>
        <x-ui.link-button :href="route('organization.branches.create')">{{ __('organization.branches.create') }}</x-ui.link-button>
    </div>

    <div wire:loading.delay wire:target="search,status,gotoPage,nextPage,previousPage">
        <x-ui.skeleton :lines="3" />
    </div>

    <div wire:loading.remove wire:target="search,status,gotoPage,nextPage,previousPage">
        @if ($branches->isEmpty())
            <x-ui.empty-state :title="__('organization.branches.empty_title')" :description="__('organization.branches.empty_description')" />
        @else
            <x-ui.table :caption="__('organization.branches.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('organization.branches.fields.code') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('organization.branches.fields.name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('organization.branches.fields.city') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('organization.branches.fields.manager_id') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('organization.branches.status') }}</th>
                        <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
                    </tr>
                </x-slot:head>
                @foreach ($branches as $branch)
                    <tr wire:key="branch-{{ $branch->ulid }}">
                        <td class="px-md py-sm font-medium">{{ $branch->code }}</td>
                        <td class="px-md py-sm">{{ $branch->name }}</td>
                        <td class="px-md py-sm">{{ $branch->city }}</td>
                        <td class="px-md py-sm">{{ $managerNames[$branch->manager_id] ?? __('organization.branches.no_manager') }}</td>
                        <td class="px-md py-sm">
                            <x-ui.badge :tone="$branch->is_active ? Tone::Success : Tone::Neutral">
                                {{ $branch->is_active ? __('shared.active') : __('shared.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="px-md py-sm">
                            <div class="flex justify-end gap-sm">
                                <a href="{{ route('organization.branches.edit', $branch) }}" wire:navigate class="text-brand underline">
                                    {{ __('shared.edit') }}<span class="sr-only"> {{ $branch->name }}</span>
                                </a>
                                <x-ui.button variant="ghost" wire:click="toggleActive('{{ $branch->ulid }}')" wire:loading.attr="disabled"
                                    wire:confirm="{{ $branch->is_active ? __('organization.branches.confirm_deactivate', ['name' => $branch->name]) : __('organization.branches.confirm_activate', ['name' => $branch->name]) }}">
                                    {{ $branch->is_active ? __('organization.branches.deactivate') : __('organization.branches.activate') }}
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $branches->links() }}</div>
        @endif
    </div>
</div>
