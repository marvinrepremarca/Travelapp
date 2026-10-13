<div class="flex flex-col gap-lg">
    <p class="text-text-subtle">{{ __('identity.role_permissions.intro') }}</p>
    <x-ui.table :caption="__('identity.role_permissions.title')">
        <x-slot:head>
            <tr>
                <th scope="col" class="px-md py-sm">{{ __('identity.role_permissions.role') }}</th>
                <th scope="col" class="px-md py-sm">{{ __('identity.role_permissions.users_column') }}</th>
                <th scope="col" class="px-md py-sm">{{ __('identity.role_permissions.permissions_column') }}</th>
                <th scope="col" class="px-md py-sm"><span class="sr-only">{{ __('shared.actions') }}</span></th>
            </tr>
        </x-slot:head>
        @foreach ($roles as $role)
            @php($count = $counts->get($role->value))
            <tr wire:key="role-{{ $role->value }}">
                <td class="px-md py-sm font-medium">{{ $role->label() }}</td>
                <td class="px-md py-sm">{{ (int) ($count->users_count ?? 0) }}</td>
                <td class="px-md py-sm">{{ (int) ($count->permissions_count ?? 0) }}</td>
                <td class="px-md py-sm text-right">
                    <a class="text-brand underline" href="{{ route('identity.roles.edit', $role->value) }}">
                        {{ $role === $locked ? __('identity.role_permissions.view') : __('identity.role_permissions.edit') }}<span class="sr-only"> {{ $role->label() }}</span>
                    </a>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
</div>
