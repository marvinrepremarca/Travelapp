<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row">
            <x-ui.field :label="__('shared.search')" for="search" :hint="__('customers.search_hint')">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" hint />
            </x-ui.field>
            <x-ui.field :label="__('customers.fields.type')" for="type">
                <x-ui.select name="type" wire:model.live="type" :placeholder="__('customers.all_types')"
                    :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            </x-ui.field>
        </div>
        <x-ui.link-button :href="route('customers.create')">{{ __('customers.create') }}</x-ui.link-button>
    </div>

    <div wire:loading.delay wire:target="search,type,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,type,gotoPage,nextPage,previousPage">
        @if ($customers->isEmpty())
            <x-ui.empty-state :title="__('customers.empty_title')" :description="__('customers.empty_description')" />
        @else
            <x-ui.table :caption="__('customers.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('customers.fields.name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('customers.fields.document_number') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('customers.fields.email') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('customers.fields.phone') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($customers as $customer)
                    <tr wire:key="customer-{{ $customer->ulid }}">
                        <td class="px-md py-sm">
                            <a href="{{ route('customers.show', $customer) }}" wire:navigate class="font-medium text-brand underline">{{ $customer->display_name }}</a>
                            <p class="text-caption text-text-subtle">{{ $customer->type->label() }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $customer->document_type->label() }} {{ $customer->maskedDocument() }}</td>
                        <td class="px-md py-sm">{{ $customer->email }}</td>
                        <td class="px-md py-sm">{{ $customer->phone }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
