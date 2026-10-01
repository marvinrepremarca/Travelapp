<div class="flex flex-col gap-lg">
    <div class="flex flex-col gap-md md:flex-row md:items-end md:justify-between">
        <div class="flex flex-col gap-md md:flex-row md:items-end">
            <x-ui.field :label="__('shared.search')" for="search" :hint="__('catalog.search_hint')">
                <x-ui.input name="search" type="search" wire:model.live.debounce.400ms="search" hint />
            </x-ui.field>
            <x-ui.field :label="__('catalog.fields.product_type')" for="type">
                <x-ui.select name="type" wire:model.live="type" :placeholder="__('catalog.all_types')"
                    :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()" />
            </x-ui.field>
        </div>
        @if ($canManage)
            <x-ui.link-button :href="route('catalog.create')">{{ __('catalog.create') }}</x-ui.link-button>
        @endif
    </div>

    <div wire:loading.delay wire:target="search,type,gotoPage,nextPage,previousPage"><x-ui.skeleton :lines="4" /></div>

    <div wire:loading.remove wire:target="search,type,gotoPage,nextPage,previousPage">
        @if ($products->isEmpty())
            <x-ui.empty-state :title="__('catalog.empty_title')" :description="__('catalog.empty_description')" />
        @else
            <x-ui.table :caption="__('catalog.title')">
                <x-slot:head>
                    <tr>
                        <th scope="col" class="px-md py-sm">{{ __('catalog.fields.name') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('catalog.fields.product_type') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('catalog.fields.destination_city') }}</th>
                        <th scope="col" class="px-md py-sm">{{ __('suppliers.standing_column') }}</th>
                    </tr>
                </x-slot:head>
                @foreach ($products as $product)
                    <tr wire:key="product-{{ $product->ulid }}">
                        <td class="px-md py-sm">
                            <a href="{{ route('catalog.show', $product) }}" wire:navigate class="font-medium text-brand underline">{{ $product->name }}</a>
                            <p class="text-caption text-text-subtle">{{ $product->code }}</p>
                        </td>
                        <td class="px-md py-sm">{{ $product->product_type->label() }}</td>
                        <td class="px-md py-sm">{{ __('catalog.destination', ['city' => $product->destination_city, 'country' => $product->destination_country]) }}</td>
                        <td class="px-md py-sm">
                            <x-ui.badge :tone="$product->is_active ? \App\Modules\Shared\Enums\Tone::Success : \App\Modules\Shared\Enums\Tone::Neutral">
                                {{ $product->is_active ? __('catalog.active') : __('catalog.inactive') }}
                            </x-ui.badge>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
            <div class="mt-md">{{ $products->links() }}</div>
        @endif
    </div>
</div>
