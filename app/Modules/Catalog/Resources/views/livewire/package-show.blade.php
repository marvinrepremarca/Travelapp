<div class="flex flex-col gap-lg">
    @include('catalog::partials.product-header')

    <x-ui.card :title="__('catalog.components.title')">
        <p class="mb-md text-text-subtle">{{ __('catalog.components.help') }}</p>
        @if ($product->components->isEmpty())
            <x-ui.empty-state :title="__('catalog.components.empty')" />
        @else
            <ol class="flex flex-col divide-y divide-border">
                @foreach ($product->components as $item)
                    <li class="flex flex-wrap items-center justify-between gap-sm py-sm" wire:key="component-{{ $item->ulid }}">
                        <div>
                            <p class="text-caption text-text-subtle">{{ __('catalog.components.day', ['day' => $item->day_offset + 1]) }}</p>
                            <a href="{{ route('catalog.show', $item->component) }}" wire:navigate class="font-medium text-brand underline">{{ $item->component->name }}</a>
                            <span class="text-caption text-text-subtle">· {{ $item->component->product_type->label() }}</span>
                        </div>
                        @if ($canManage)
                            <x-ui.button variant="ghost" wire:click="removeComponent('{{ $item->ulid }}')" wire:loading.attr="disabled"
                                wire:confirm="{{ __('catalog.components.confirm_remove') }}">
                                {{ __('catalog.components.remove') }}<span class="sr-only"> {{ $item->component->name }}</span>
                            </x-ui.button>
                        @endif
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($canManage)
            <form wire:submit="addComponent" class="mt-md grid gap-md md:grid-cols-3 md:items-end" novalidate>
                <x-ui.field :label="__('catalog.component_fields.product')" for="component.product">
                    <x-ui.select name="component.product" wire:model="component.product" :options="$candidates" :placeholder="__('catalog.components.choose')" required />
                </x-ui.field>
                <x-ui.field :label="__('catalog.component_fields.day_offset')" for="component.day_offset">
                    <x-ui.input name="component.day_offset" type="number" min="0" wire:model="component.day_offset" required />
                </x-ui.field>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="addComponent">{{ __('catalog.components.add') }}</x-ui.button>
            </form>
        @endif
    </x-ui.card>
</div>
